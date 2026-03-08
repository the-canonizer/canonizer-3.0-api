FROM php:8.4-fpm-alpine

RUN apk add --no-cache autoconf g++ make zip unzip \
    && pecl install mongodb \
    && docker-php-ext-enable mongodb \
    && docker-php-ext-install pdo_mysql

RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

RUN mkdir -p /opt/canonizer/
COPY . /opt/canonizer/
COPY ./docker/docker.env /opt/canonizer/.env

WORKDIR /opt/canonizer/

RUN mkdir -p bootstrap/cache storage/logs storage/framework/sessions storage/framework/views storage/framework/cache \
    && chmod -R 777 bootstrap/cache storage

RUN composer update

RUN php artisan passport:keys

RUN apk add --no-cache supervisor
RUN mkdir -p /etc/supervisor.d/
