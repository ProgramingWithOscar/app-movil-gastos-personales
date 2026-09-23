# Nginx interno del stack.
#
# Necesita los archivos públicos de Laravel para resolver rutas antes de pasar
# el PHP a php-fpm. Se copian desde la imagen del backend en vez de compartirse
# por un volumen: Docker solo rellena un volumen la primera vez, y tras cada
# despliegue se quedaría sirviendo la versión vieja.

ARG IMAGEN_BACKEND=gastos-backend:latest

FROM ${IMAGEN_BACKEND} AS app

FROM nginx:1.27-alpine

COPY --from=app /var/www/html/public /var/www/html/public
COPY default.conf /etc/nginx/conf.d/default.conf
