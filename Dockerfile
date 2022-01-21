FROM php:8.1-fpm-alpine

# Install pdo_mysql package
RUN docker-php-ext-install pdo_mysql

# Installing the composer
RUN php -r "readfile('http://getcomposer.org/installer');" | php -- --install-dir=/usr/bin/ --filename=composer

# Configure the Application
RUN mkdir -p /opt/canonizer/
COPY . /opt/canonizer/

# Copy the docker environment file
COPY ./docker/docker.env /opt/canonizer/.env

WORKDIR /opt/canonizer/

# update composer
RUN composer update

# Install Supervisor
RUN apk add --no-cache zip unzip supervisor

# Configuration of Supervisor
RUN mkdir -p /etc/supervisor.d/
# Add the configuration later