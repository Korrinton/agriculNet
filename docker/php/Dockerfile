FROM php:8.3-fpm-alpine

# Dependencias del sistema
RUN apk add --no-cache \
    bash \
    git \
    curl \
    autoconf \
    g++ \
    make \
    libpng-dev \
    libzip-dev \
    postgresql-dev \
    oniguruma-dev \
    icu-dev \
    freetype-dev \
    libjpeg-turbo-dev

# Extensiones PHP necesarias para Laravel + PostgreSQL
RUN docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install \
        pdo \
        pdo_pgsql \
        pgsql \
        mbstring \
        zip \
        exif \
        bcmath \
        gd \
        intl \
        pcntl

# Redis extension vía PECL
RUN pecl install redis && docker-php-ext-enable redis

# Composer
COPY --from=composer:2.7 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

# Entrypoint para migraciones y arranque
COPY docker/php/entrypoint.sh /usr/local/bin/entrypoint.sh
RUN chmod +x /usr/local/bin/entrypoint.sh

EXPOSE 9000

ENTRYPOINT ["entrypoint.sh"]
CMD ["php-fpm"]
