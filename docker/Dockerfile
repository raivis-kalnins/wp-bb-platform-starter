FROM php:8.4-apache

ARG WP_CLI_VERSION=2.12.0

RUN apt-get update && apt-get install -y --no-install-recommends \
    ca-certificates curl git unzip libfreetype6-dev libicu-dev libjpeg62-turbo-dev \
    libonig-dev libpng-dev libwebp-dev libzip-dev mariadb-client \
 && docker-php-ext-configure gd --with-freetype --with-jpeg --with-webp \
 && docker-php-ext-install -j$(nproc) bcmath exif gd intl mbstring mysqli opcache pdo_mysql zip \
 && pecl install redis \
 && docker-php-ext-enable redis \
 && a2enmod rewrite headers expires \
 && rm -rf /var/lib/apt/lists/*

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer
RUN curl -fsSL -o /usr/local/bin/wp https://github.com/wp-cli/wp-cli/releases/download/v${WP_CLI_VERSION}/wp-cli-${WP_CLI_VERSION}.phar \
 && chmod +x /usr/local/bin/wp

COPY docker/apache/000-default.conf /etc/apache2/sites-available/000-default.conf
WORKDIR /var/www/html
