FROM php:8.2-fpm
EXPOSE 5173

# COPY php.ini
COPY ./docker/php/php.ini /usr/local/etc/php/php.ini

# Composer install
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

# install packages including Node.js
RUN apt-get update && \
    curl -fsSL https://deb.nodesource.com/setup_20.x | bash - && \
    apt-get -y install \
    nodejs\
    git \
    zip \
    unzip \
    vim \
    && docker-php-ext-install pdo_mysql bcmath \
    && pecl install redis \
    && docker-php-ext-enable redis

WORKDIR /

RUN mkdir /app

RUN chmod -R 777 /app

WORKDIR /app