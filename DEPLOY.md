# Deploying the Mutual Loan Fund

Everything runs in Docker, and everything the application produces stays on the
host: source, `vendor/`, `.env`, uploaded receipts, logs and the PostgreSQL data
directory are all bind-mounted. There are no named Docker volumes, so nothing is
hidden inside the Docker daemon, and edits made on the server take effect on the
next request without a rebuild.

## What runs

| Container       | Image                    | Role                                              |
|-----------------|--------------------------|---------------------------------------------------|
| `mlf-web`       | `nginx:1.27-alpine`      | Serves `public/`, proxies PHP to `mlf-app`        |
| `mlf-app`       | built from `docker/php`  | PHP-FPM 8.3, runs migrations on boot              |
| `mlf-db`        | `postgres:16-alpine`     | PostgreSQL 16                                     |
| `mlf-scheduler` | built from `docker/php`  | `php artisan schedule:work`                       |

## Host paths

| Path                     | Contents                                       |
|--------------------------|------------------------------------------------|
| `./`                     | The whole application, mounted at `/var/www/html` |
| `./vendor`               | Composer dependencies, installed on first boot |
| `./storage/app/private/receipts` | Uploaded receipts — private, never web-served |
| `./storage/logs`         | Application logs                               |
| `./docker/data/postgres` | PostgreSQL data directory                      |
| `./docker/data/nginx`    | nginx access and error logs                    |
| `./docker/data/composer-cache` | Composer cache, so restarts are fast     |

## First deployment

```bash
git clone <this repository> mutual-loan-fund
cd mutual-loan-fund

cp .env.example .env
```

Edit `.env`. At a minimum, set these:

```ini
APP_URL=https://fund.example.org      # the address people will actually use
DB_PASSWORD=<a long random password>
APP_UID=1000                          # output of `id -u` for the owning user
APP_GID=1000                          # output of `id -g`
```

`APP_UID` / `APP_GID` matter: the PHP workers run as that user so files written
into the bind mount (receipts, logs, caches) stay owned by your server account
rather than by root.

Then:

```bash
docker compose up -d --build
docker compose logs -f app       # watch the first boot
```

On that first boot the app container installs Composer dependencies, generates
`APP_KEY` into `.env`, creates the storage directories and runs the migrations.
It takes a couple of minutes; afterwards restarts are quick.

The application is then on `http://<host>:8080` (change `HTTP_PORT` in `.env`).

Register the first account through the web interface, then create a fund — that
account becomes its administrator.

### Demo data (non-production only)

```bash
docker compose exec -u 1000:1000 app php artisan db:seed
```

This builds a worked example: two funds' worth of activity, a published policy,
a treasury, verified contributions, a disbursed loan and a partial repayment.
It refuses to run when `APP_ENV=production`.

## Everyday operations

Run artisan as the owning user so new files keep the right ownership:

```bash
docker compose exec -u 1000:1000 app php artisan migrate
docker compose exec -u 1000:1000 app php artisan tinker
docker compose logs -f app
docker compose restart app
```

Deploying a change:

```bash
git pull
docker compose restart app       # only needed if composer.json changed
```

Because no config, route or view cache is built, a `git pull` alone is enough
for ordinary code and template changes.

## Behind a TLS reverse proxy

Publish `HTTP_PORT` on the loopback interface and terminate TLS in front of it.
With `APP_ENV=production` the application forces `https://` in generated URLs.
Set these in `.env` once TLS is in place:

```ini
SESSION_SECURE_COOKIE=true
```

An example nginx front end:

```nginx
server {
    listen 443 ssl http2;
    server_name fund.example.org;

    ssl_certificate     /etc/letsencrypt/live/fund.example.org/fullchain.pem;
    ssl_certificate_key /etc/letsencrypt/live/fund.example.org/privkey.pem;

    location / {
        proxy_pass http://127.0.0.1:8080;
        proxy_set_header Host              $host;
        proxy_set_header X-Real-IP         $remote_addr;
        proxy_set_header X-Forwarded-For   $proxy_add_x_forwarded_for;
        proxy_set_header X-Forwarded-Proto $scheme;
    }
}
```

## Blockchain verification

Out of the box `BLOCKCHAIN_VERIFIER=manual`: the fund records the transaction
hash a member submits and an administrator confirms it against a block explorer.
Nothing is ever marked chain-verified without a real check having happened.

To let the server check TRC-20 transfers itself:

```ini
BLOCKCHAIN_VERIFIER=trongrid
TRONGRID_API_KEY=<your key>
```

The verifier reads the treasury address's own TRC-20 transfer list, matches the
hash, address and amount, and treats a transaction as final only once the
solidity node (irreversible blocks only) has it. If TronGrid cannot be reached
it reports that rather than guessing, and the transaction stays pending.

Either way the `(network, tx_hash)` unique index means the same transfer can
never be credited twice.

## Exchange rates

Rates are entered by hand at `/exchange-rates` by anyone who administers a fund,
quoted as *how many units equal one gram of 18K gold*. A market quote in troy
ounces of 24K gold can be entered instead and is converted mathematically.

If no rate exists for a date, the most recent earlier one is used and labelled as
carried forward. A rate is never invented — an operation that genuinely needs one
(a cross-currency posting) is refused until a rate is entered.

## Backups

Two things need backing up:

```bash
# The database
docker compose exec db pg_dump -U mlf mutual_loan_fund | gzip > backup-$(date +%F).sql.gz

# The receipts
tar czf receipts-$(date +%F).tar.gz storage/app/private/receipts
```

`./docker/data/postgres` can also be copied directly, but only while the `db`
container is stopped.

## Health check

`GET /up` returns 200 once the framework has booted. Point your monitoring at it.

## Testing on the server

```bash
docker compose exec db createdb -U mlf mutual_loan_fund_test
docker compose exec -u 1000:1000 app php artisan test
```

The suite covers the invariants that matter: tenant isolation, balanced entries,
immutability of posted entries and published policies, loan eligibility
enforcement, duplicate blockchain transfers, closed periods and the preservation
of historical valuations.
