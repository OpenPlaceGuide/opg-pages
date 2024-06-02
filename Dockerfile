FROM composer:2.6.6 as composer

FROM php:8.1-apache-buster

RUN apt-get update && apt-get install -y zip

RUN apt-get -y update \
  && apt-get install -y libicu-dev \
  && docker-php-ext-configure intl \
  && docker-php-ext-install intl

RUN apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libwebp-dev \
    libxpm-dev \
    zlib1g-dev && \
    docker-php-ext-configure gd --enable-gd --with-webp --with-jpeg \
    --with-xpm --with-freetype && \
    docker-php-ext-install gd

RUN a2enmod rewrite

ENV LOG_CHANNEL=stderr

RUN sed -ri -e 's!/var/www/html!/var/www/html/public!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!/var/www/html/public!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf

RUN sed -ri -e 's!^</!<Directory "/var/www/html/public">\nAllowOverride all\n</Directory>\n</!g' /etc/apache2/sites-available/*.conf

COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/storage || true
RUN chown -R www-data:www-data /var/www/html/bootstrap/cache || true

COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev
