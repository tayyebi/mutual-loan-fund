#!/bin/bash
# Container entrypoint.
#
# The application tree is bind-mounted from the host, so everything that a normal
# image would bake in at build time is done here instead: dependency install, key
# generation, storage scaffolding and migrations. All artifacts (vendor/, .env,
# storage/, database) live on the host and survive container recreation.

set -euo pipefail

APP_DIR=/var/www/html
ROLE="${CONTAINER_ROLE:-app}"
RUN_AS="${APP_UID:-1000}:${APP_GID:-1000}"

log() { printf '[entrypoint] %s\n' "$*"; }

as_app() { su-exec "$RUN_AS" "$@"; }

cd "$APP_DIR"

# ---------------------------------------------------------------- environment --
if [ ! -f .env ]; then
    if [ -f .env.example ]; then
        log ".env missing; creating it from .env.example"
        cp .env.example .env
    else
        log "FATAL: neither .env nor .env.example is present"
        exit 1
    fi
fi

# --------------------------------------------------------------- directories --
mkdir -p \
    storage/app/private/receipts \
    storage/framework/cache/data \
    storage/framework/sessions \
    storage/framework/testing \
    storage/framework/views \
    storage/logs \
    bootstrap/cache

# Only fix ownership of the writable paths; never chown the whole mounted repo.
chown -R "$RUN_AS" storage bootstrap/cache 2>/dev/null || true

# ---------------------------------------------------------------- ssl cert --
# A self-signed certificate is generated once and kept in the host-mounted
# certs directory that nginx serves on port 8088. It is only regenerated when
# the files are missing (e.g. first boot after a clean checkout), so the
# certificate persists across container restarts.
CERTS_DIR=/etc/nginx/certs
CERT_FILE="$CERTS_DIR/cert.pem"
KEY_FILE="$CERTS_DIR/key.pem"

if [ ! -f "$CERT_FILE" ] || [ ! -f "$KEY_FILE" ]; then
    log "generating self-signed TLS certificate into $CERTS_DIR"
    mkdir -p "$CERTS_DIR"
    openssl req -x509 -nodes -newkey rsa:4096 -sha256 -days 3650 \
        -keyout "$KEY_FILE" -out "$CERT_FILE" \
        -subj "/C=IR/O=Mutual Loan Fund/OU=Self-Signed/CN=localhost" \
        -addext "subjectAltName=DNS:localhost,IP:127.0.0.1"
    chmod 600 "$KEY_FILE"
fi

# -------------------------------------------------------------- dependencies --
if [ ! -f vendor/autoload.php ] || [ "${COMPOSER_INSTALL_ON_BOOT:-auto}" = "always" ]; then
    log "installing PHP dependencies with composer"
    as_app composer install --no-interaction --prefer-dist --no-progress
fi

# ------------------------------------------------------------------- app key --
if ! grep -qE '^APP_KEY=base64:' .env; then
    log "generating APP_KEY"
    as_app php artisan key:generate --force --no-interaction
fi

# ----------------------------------------------------------- wait for the db --
wait_for_db() {
    local tries=60
    until as_app php -r '
        $dsn = sprintf("pgsql:host=%s;port=%s;dbname=%s",
            getenv("DB_HOST") ?: "db",
            getenv("DB_PORT") ?: "5432",
            getenv("DB_DATABASE") ?: "mutual_loan_fund");
        try { new PDO($dsn, getenv("DB_USERNAME"), getenv("DB_PASSWORD")); exit(0); }
        catch (Throwable $e) { exit(1); }
    ' >/dev/null 2>&1; do
        tries=$((tries - 1))
        if [ "$tries" -le 0 ]; then
            log "FATAL: database did not become reachable"
            exit 1
        fi
        sleep 2
    done
}

log "waiting for PostgreSQL"
wait_for_db
log "database is reachable"

# ------------------------------------------------------------------ storage ---
if [ ! -e public/storage ]; then
    as_app php artisan storage:link --no-interaction || true
fi

# ----------------------------------------------------------------- migrations --
if [ "$ROLE" = "app" ] && [ "${AUTO_MIGRATE:-true}" = "true" ]; then
    log "running database migrations"
    as_app php artisan migrate --force --no-interaction
fi

# Config/route caches are deliberately NOT built: they would freeze the code and
# defeat the "edit on the host, effective immediately" requirement.
as_app php artisan config:clear --no-interaction >/dev/null 2>&1 || true
as_app php artisan route:clear  --no-interaction >/dev/null 2>&1 || true
as_app php artisan view:clear   --no-interaction >/dev/null 2>&1 || true

# ---------------------------------------------------------------------- role --
case "$ROLE" in
    app)
        # php-fpm's master runs as root; its workers are pinned to the host uid/gid
        # so that files written into the bind mount stay owned by the server user.
        printf '[www]\nuser = %s\ngroup = %s\n' "${APP_UID:-1000}" "${APP_GID:-1000}" \
            > /usr/local/etc/php-fpm.d/zzz-user.conf
        log "starting php-fpm (workers as ${RUN_AS})"
        exec "$@"
        ;;
    scheduler)
        log "starting Laravel scheduler"
        exec su-exec "$RUN_AS" php artisan schedule:work --no-interaction
        ;;
    queue)
        log "starting queue worker"
        exec su-exec "$RUN_AS" php artisan queue:work --no-interaction --tries=1
        ;;
    console)
        exec su-exec "$RUN_AS" "$@"
        ;;
    *)
        log "unknown CONTAINER_ROLE '$ROLE'"
        exit 1
        ;;
esac
