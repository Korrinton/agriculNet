#!/bin/sh
set -e

# Instala dependencias si el volumen vendor está vacío
if [ ! -f "/var/www/html/vendor/autoload.php" ]; then
    echo "Instalando dependencias de Composer..."
    composer install --no-interaction --prefer-dist
fi

# Solo php-fpm corre migraciones al arrancar; workers y scheduler no
if [ "$1" = "php-fpm" ]; then
    echo "Ejecutando migraciones..."
    php artisan migrate --force

    echo "Limpiando cachés..."
    php artisan config:cache
    php artisan route:cache
    php artisan view:cache
fi

exec "$@"
