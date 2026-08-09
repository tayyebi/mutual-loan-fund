# PHP-FPM image for the Mutual Loan Fund application.
#
# The application source is NOT copied into the image: docker-compose bind-mounts
# the repository over /var/www/html so that edits on the host are live inside the
# container. Dependencies are therefore installed at container start (entrypoint),
# not at build time.

FROM php:8.3-fpm-alpine

ARG APP_UID=1000
ARG APP_GID=1000
# Point apk at a reachable mirror (e.g. https://mirror.arvancloud.ir/alpine)
# when dl-cdn.alpinelinux.org is unreachable from the build host.
ARG ALPINE_MIRROR=https://dl-cdn.alpinelinux.org/alpine

RUN set -eux; \
    VER=$(cat /etc/alpine-release | cut -d. -f1-2); \
    printf '%s/v%s/main\n%s/v%s/community\n' \
        "$ALPINE_MIRROR" "$VER" "$ALPINE_MIRROR" "$VER" > /etc/apk/repositories

RUN apk add --no-cache \
        bash \
        git \
        unzip \
        icu-dev \
        libzip-dev \
        oniguruma-dev \
        postgresql-dev \
        postgresql-client \
        su-exec \
        tzdata \
    && apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && docker-php-ext-configure intl \
    && docker-php-ext-install -j"$(nproc)" \
        bcmath \
        intl \
        mbstring \
        opcache \
        pcntl \
        pdo_pgsql \
        pgsql \
        zip \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# Run as a host-matching uid/gid so bind-mounted files stay writable on the server.
RUN set -eux; \
    if ! getent group "${APP_GID}" >/dev/null; then addgroup -g "${APP_GID}" app; fi; \
    if ! getent passwd "${APP_UID}" >/dev/null; then \
        adduser -u "${APP_UID}" -G "$(getent group "${APP_GID}" | cut -d: -f1)" -h /home/app -s /bin/bash -D app; \
    fi; \
    mkdir -p /home/app/.composer/cache; \
    chown -R "${APP_UID}:${APP_GID}" /home/app

ENV COMPOSER_HOME=/home/app/.composer \
    COMPOSER_ALLOW_SUPERUSER=1 \
    APP_UID=${APP_UID} \
    APP_GID=${APP_GID}

COPY docker/php/php.ini /usr/local/etc/php/conf.d/zz-app.ini
COPY docker/php/www.conf /usr/local/etc/php-fpm.d/zz-www.conf
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

WORKDIR /var/www/html

EXPOSE 9000

ENTRYPOINT ["/usr/local/bin/entrypoint"]
CMD ["php-fpm", "-F"]
