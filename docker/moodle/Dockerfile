FROM php:8.3-fpm-bookworm

ENV DEBIAN_FRONTEND=noninteractive
ARG MOODLE_REPO=https://github.com/moodle/moodle.git
ARG MOODLE_VERSION=v5.2.1
ARG INCLUDE_MOODLE_SOURCE=false

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        ca-certificates \
        curl \
        git \
        gettext-base \
        libfreetype6-dev \
        libicu-dev \
        libjpeg62-turbo-dev \
        libonig-dev \
        libpng-dev \
        libpq-dev \
        libsodium-dev \
        libxml2-dev \
        libzip-dev \
        nginx \
        postgresql-client \
        unzip \
        wget \
        zip \
    && rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j"$(nproc)" \
        exif \
        gd \
        intl \
        mbstring \
        opcache \
        pdo_pgsql \
        pgsql \
        soap \
        zip

ARG PHPREDIS_VERSION=6.3.0
RUN git clone --depth 1 --branch "${PHPREDIS_VERSION}" https://github.com/phpredis/phpredis.git /usr/src/php/ext/redis \
    && docker-php-ext-install redis

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

COPY docker/moodle/php.ini /usr/local/etc/php/conf.d/zz-moodle.ini
COPY docker/moodle/php-fpm-env.conf /usr/local/etc/php-fpm.d/zz-moodle-env.conf
COPY docker/moodle/nginx.conf /etc/nginx/nginx.conf
COPY docker/moodle/default.conf /etc/nginx/conf.d/default.conf
COPY docker/moodle/config.php /usr/local/share/moodle/config.php
COPY docker/moodle/entrypoint.sh /usr/local/bin/moodle-entrypoint
COPY docker/moodle/cron-loop.sh /usr/local/bin/moodle-cron-loop
COPY moodle-overrides/ /usr/local/share/moodle-overrides/

RUN chmod +x /usr/local/bin/moodle-entrypoint /usr/local/bin/moodle-cron-loop \
    && mkdir -p \
        /tmp/nginx/client_temp \
        /tmp/nginx/fastcgi_temp \
        /tmp/nginx/proxy_temp \
        /tmp/nginx/scgi_temp \
        /tmp/nginx/uwsgi_temp \
        /usr/local/var/log \
        /usr/local/var/run \
        /var/lib/nginx \
        /var/log/nginx \
        /var/www/moodle \
        /var/www/moodledata \
    && chown -R www-data:www-data \
        /tmp/nginx \
        /usr/local/var/log \
        /usr/local/var/run \
        /var/lib/nginx \
        /var/log/nginx \
        /var/www/moodle \
        /var/www/moodledata \
    && rm -f /etc/nginx/sites-enabled/default

RUN if [ "${INCLUDE_MOODLE_SOURCE}" = "true" ]; then \
        git clone --depth 1 --branch "${MOODLE_VERSION}" "${MOODLE_REPO}" /var/www/moodle; \
        git config --global --add safe.directory /var/www/moodle; \
        cp -a /usr/local/share/moodle-overrides/. /var/www/moodle/; \
        composer --working-dir=/var/www/moodle install --no-interaction --prefer-dist --no-dev --optimize-autoloader; \
        chown -R www-data:www-data /var/www/moodle; \
    fi

WORKDIR /var/www/moodle

USER www-data

ENTRYPOINT ["moodle-entrypoint"]
CMD ["moodle-web"]
