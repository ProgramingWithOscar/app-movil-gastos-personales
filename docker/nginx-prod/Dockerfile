# Nginx interno del stack.
#
# Necesita los archivos públicos de Laravel para resolver rutas antes de pasar
# el PHP a php-fpm. Se copian del repositorio, no de la imagen del backend:
# depender de ella obligaba a construir en un orden concreto, y Compose lanza
# las construcciones en paralelo.
#
# En esta API `public/` son cuatro archivos estáticos que no genera ningún
# proceso de compilación, así que el repositorio es su fuente de verdad.

FROM nginx:1.27-alpine

COPY backend/public/ /var/www/html/public/
COPY docker/nginx-prod/default.conf /etc/nginx/conf.d/default.conf
