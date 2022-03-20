FROM composer:latest AS composer
FROM php:7.4-fpm-alpine

RUN apk add --no-cache bash

# PHP extensions installer
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Composer
COPY --from=composer /usr/bin/composer /usr/bin/composer
RUN mkdir /.composer && chown -R 1000:1000 /.composer

RUN install-php-extensions pdo_mysql gmp gd zip exif pcntl

# Setup Working Dir
WORKDIR /app

# setup
COPY . /app
RUN [ ! -e ".env" ] && cp .env.example .env || echo 0
RUN composer install --no-dev --no-interaction --ansi

USER 1000:1000