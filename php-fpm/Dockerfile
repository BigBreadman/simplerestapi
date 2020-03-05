FROM php:7.3-fpm

RUN apt-get update && apt-get install -y mariadb-client && \
    /usr/local/bin/docker-php-ext-install pdo pdo_mysql

WORKDIR "/application"
