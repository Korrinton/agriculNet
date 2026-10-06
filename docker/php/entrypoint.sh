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

    # En producción se cachean config, rutas y vistas. En desarrollo no: con la config cacheada
    # los tests leen la BD de desarrollo (y TestCase los aborta) y las rutas nuevas no aparecen.
    if [ "$APP_ENV" = "production" ]; then
        # Con HTTP, depuración activa, copias sin cifrar o el usuario de pruebas, no se arranca
        echo "Comprobando la configuración de seguridad..."
        php artisan seguridad:comprobar || { echo "Arranque detenido: corrige lo anterior en .env"; exit 1; }

        echo "Cacheando configuración, rutas y vistas..."
        php artisan config:cache
        php artisan route:cache
        php artisan view:cache
    else
        echo "Limpiando cachés de configuración, rutas y vistas (APP_ENV=$APP_ENV)..."
        php artisan config:clear
        php artisan route:clear
        php artisan view:clear
    fi
fi

exec "$@"
