#!/bin/sh
set -e

if [ ! -f .env ]; then
  cp .env.example .env
fi

if [ -z "${APP_KEY}" ] && ! grep -q '^APP_KEY=base64' .env; then
  php artisan key:generate --force
fi

echo "Esperando la base de datos en ${DB_HOST:-db}:${DB_PORT:-3306}…"
until php -r "new PDO('mysql:host='.getenv('DB_HOST').';port='.getenv('DB_PORT'), getenv('DB_USERNAME'), getenv('DB_PASSWORD'));" >/dev/null 2>&1; do
  sleep 2
done

php artisan migrate --force

# En local el código está montado desde el host: cachear rutas y configuración
# haría que los cambios no se vieran hasta reiniciar el contenedor.
if [ "${APP_ENV}" = "local" ]; then
  php artisan config:clear
  php artisan route:clear
else
  php artisan config:cache
  php artisan route:cache
  # Los avatares viven en un volumen; el enlace se rehace en cada arranque
  # porque la capa de la imagen es efímera.
  php artisan storage:link --force
fi

chown -R www-data:www-data storage bootstrap/cache

exec "$@"
