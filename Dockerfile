FROM composer:latest AS composer

COPY . /var/www/html
RUN cd /var/www/html && composer install --no-dev --no-scripts


FROM node:22 AS node

COPY . /var/www/html
WORKDIR /var/www/html
RUN npm install && npm run build


FROM cgr.dev/chainguard/wolfi-base:latest

ARG PHP_VERSION=8.4

RUN <<EOF
set -eo pipefail
apk add --no-cache \
    php-${PHP_VERSION}-fpm
adduser -u 82 www-data -D
mkdir -p /var/www/html
chown www-data:www-data /var/www/html
EOF

WORKDIR /var/www/html
ENV PHP_FPM_USER=www-data \
    PHP_FPM_GROUP=www-data \
    PHP_FPM_ACCESS_LOG=/proc/self/fd/2 \
    PHP_FPM_LISTEN=[::]:9000 \
    PHP_FPM_PM=dynamic \
    PHP_FPM_PM_MAX_CHILDREN=5 \
    PHP_FPM_PM_START_SERVERS=2 \
    PHP_FPM_PM_MIN_SPARE_SERVERS=1 \
    PHP_FPM_PM_MAX_SPARE_SERVERS=3 \
    PHP_FPM_PM_MAX_REQUESTS=0 \
    PHP_FPM_PM_STATUS_PATH=/-/fpm/status \
    PHP_FPM_PING_PATH=/-/fpm/ping \
    PHP_ERROR_REPORTING=E_ALL\
    PHP_UPLOAD_MAX_FILESIZE=2M \
    PHP_POST_MAX_SIZE=2M \
    PHP_MAX_EXECUTION_TIME=30 \
    PHP_MEMORY_LIMIT=128M \
    PHP_SESSION_HANDLER=files \
    PHP_SESSION_SAVE_PATH= \
    PHP_SESSION_GC_PROBABILITY=1

COPY --link docker/rootfs /


ENV PHP_FPM_LISTEN=/tmp/php-fpm.sock \
    PHP_FPM_ACCESS_LOG=/dev/null

RUN <<EOF
set -eo pipefail
apk add --no-cache \
    hivemind \
    nginx
EOF

EXPOSE 8000

RUN apk add --no-cache zip php-${PHP_VERSION} php-${PHP_VERSION}-intl php-${PHP_VERSION}-gd php-${PHP_VERSION}-cgi php-${PHP_VERSION}-phar php-${PHP_VERSION}-iconv php-${PHP_VERSION}-mbstring php-${PHP_VERSION}-openssl php-${PHP_VERSION}-dom php-${PHP_VERSION}-curl php-${PHP_VERSION}-simplexml php-${PHP_VERSION}-pdo php-${PHP_VERSION}-pdo_sqlite curl

#ENV LOG_CHANNEL=stderr

COPY . /var/www/html

RUN mkdir -p storage/framework/cache/data storage/framework/sessions storage/framework/views
RUN chown -R www-data:www-data /var/www/html/storage || true
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache || true
RUN curl https://taginfo.openstreetmap.org/download/taginfo-wiki.db.bz2 | bunzip2 > database/taginfo-wiki.db

COPY --from=composer /var/www/html/vendor /var/www/html/vendor
COPY --from=composer /usr/bin/composer /usr/bin/composer
COPY --from=node /var/www/html/public/build /var/www/html/public/build

RUN composer dump-autoload

# RUN npm install
# RUN npm run build
# RUN mkdir -p public/assets \
#    ln -s storage/app/repositories/opg-data-ethiopia/places public/assets/ethiopia

CMD ["/usr/bin/hivemind", "/etc/Procfile"]
# CMD ["php", "artisan"]
