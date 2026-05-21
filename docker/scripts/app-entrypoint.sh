#!/usr/bin/env sh
set -e

cd /var/www/html

if [ -f composer.json ] && [ ! -f vendor/autoload.php ]; then
    echo "Composer dependencies are missing. Running composer install..."
    composer install --no-interaction --prefer-dist --optimize-autoloader
fi

if [ -d storage ] && [ -d bootstrap/cache ]; then
    chown -R www-data:www-data storage bootstrap/cache 2>/dev/null || true
    chmod -R ug+rwX storage bootstrap/cache 2>/dev/null || true
fi

exec "$@"
