# Desplegar el backend en el VPS

Resultado: **https://kuenta.otech-labs.com/api** respondiendo, conviviendo con
valiu, quickhom, cv-ai y Jenkins sin tocar nada de lo que ya funciona.

---

## Cómo encaja en tu servidor

Tu VPS ya tiene un contenedor **`main_nginx`** que es el único dueño de los
puertos 80 y 443, y reparte por subdominio hacia el resto de apps. Los
certificados los emite certbot en el host, y ya hay un subdominio de otech-labs
funcionando así (`cv-ai.otech-labs.com`).

Este stack se suma a ese esquema, no lo reemplaza:

```
internet ─► main_nginx (80/443, certificados)
              └─ kuenta.otech-labs.com ─► gastos-web ─► gastos-backend ─► gastos-db
```

`gastos-web` **no se asoma a internet**. Solo lo alcanza `main_nginx`, por
nombre de contenedor a través de la red `valiu_net`. También se publica en
`127.0.0.1:8300` para poder probarlo desde el propio servidor.

> Esto se aparta de cómo está montado `cv-ai.otech-labs.com`, que publica su
> puerto 8084 en `0.0.0.0` y el proxy le habla por `172.17.0.1:8084`. Funciona,
> pero deja la app accesible por HTTP directo en `85.31.224.173:8084`, saltándose
> el certificado. Hablando por nombre de contenedor no hace falta abrir nada.

> **Por qué no un Caddy propio:** habría querido los puertos 80 y 443, que ya
> están ocupados. Arrancarlo habría tumbado todo lo demás.

---

## 1. DNS

Un registro **A** de `kuenta.otech-labs.com` apuntando a la IP del VPS.

```bash
dig +short kuenta.otech-labs.com
```

Tiene que devolver la IP del servidor **antes** de pedir el certificado. Si no
ha propagado, la emisión falla.

## 2. Traer el código

```bash
ssh root@85.31.224.173
git clone https://github.com/ProgramingWithOscar/app-movil-gastos-personales.git /opt/gastos-personales
cd /opt/gastos-personales
```

El repositorio es público, así que clonar no pide credenciales.

## 3. Configurar

```bash
cp .env.production.example .env
nano .env
```

`RED_PROXY` ya viene con `valiu_net`, que es la red del `main_nginx` de este
servidor. Solo faltan la clave y las contraseñas.

Genera la clave de la aplicación y las contraseñas:

```bash
docker run --rm php:8.3-cli php -r "echo 'base64:'.base64_encode(random_bytes(32)).PHP_EOL;"
openssl rand -base64 32   # una para DB_PASSWORD, otra para DB_ROOT_PASSWORD
```

**`APP_KEY` cifra los tokens de sesión.** Si la cambias después, todo el mundo
queda desconectado. Guárdala donde guardes tus secretos.

El compose usa `${VAR:?falta VAR}`: si olvidas una variable **no arranca**, en
lugar de levantarse con la contraseña `secret` del entorno de desarrollo.

## 4. Levantar el stack

```bash
docker compose -f docker-compose.prod.yml up -d --build
```

La primera vez tarda: compila PHP, arranca MySQL y corre las migraciones.

Comprueba que responde **desde el servidor**, antes de meter el proxy en la
ecuación:

```bash
curl http://127.0.0.1:8300/api/ping
# → {"message":"pong"}
```

Si eso funciona, el stack está bien y lo que quede por resolver es del proxy.

## 5. Certificado

El webroot de este servidor es el de valiu, que es lo que `main_nginx` sirve
como `/var/www/certbot`:

```bash
certbot certonly --webroot -w /var/www/valiu-investments/public/certbot \
  -d kuenta.otech-labs.com
```

## 6. Publicar el subdominio

```bash
cp docker/nginx-prod/vhost-main-nginx.conf \
   /var/www/nginx-proxy/sites/kuenta.otech-labs.com.conf
```

Y recarga:

```bash
docker exec main_nginx nginx -t      # valida antes de aplicar
docker exec main_nginx nginx -s reload
```

`nginx -t` primero, siempre: un error de sintaxis en el reload dejaría **todos**
tus sitios caídos, no solo este.

## 7. Comprobar

```bash
curl https://kuenta.otech-labs.com/api/ping
# → {"message":"pong"}
```

## 8. Crear tu cuenta

**No hay usuarios de prueba en producción.** El seeder con `oscar@gastos.test` y
contraseña `password` es solo de desarrollo y no se ejecuta aquí.

```bash
curl -X POST https://kuenta.otech-labs.com/api/auth/register \
  -H 'Content-Type: application/json' -H 'Accept: application/json' \
  -d '{"name":"Oscar","email":"tu@correo.com",
       "password":"UnaBuenaClave1","password_confirmation":"UnaBuenaClave1"}'
```

Mínimo 8 caracteres, con mayúscula, minúscula y número, y que no aparezca en
filtraciones conocidas.

---

## Actualizar

```bash
cd /opt/gastos-personales
git pull
docker compose -f docker-compose.prod.yml up -d --build
```

Las migraciones corren solas al arrancar.

## Copias de seguridad

Lo único irreemplazable es la base de datos:

```bash
docker compose -f docker-compose.prod.yml exec -T db \
  mysqldump -u root -p"$DB_ROOT_PASSWORD" gastos | gzip > kuenta-$(date +%F).sql.gz
```

Ponlo en un cron diario y **copia el resultado fuera del servidor**. Una copia
que vive en la misma máquina que los datos no protege del caso que más duele:
perder la máquina.

Restaurar:

```bash
gunzip < kuenta-2026-09-23.sql.gz | \
  docker compose -f docker-compose.prod.yml exec -T db mysql -u root -p"$DB_ROOT_PASSWORD" gastos
```

## Entrar a la base de datos

No publica ningún puerto, a propósito — a diferencia de `valiu_db`, que tiene el
3306 abierto a internet. Desde el servidor:

```bash
docker compose -f docker-compose.prod.yml exec db mysql -u gastos -p gastos
```

Desde tu máquina, con un túnel temporal:

```bash
ssh -L 3307:localhost:3306 root@tu-servidor
```

---

## Diferencias con el entorno de desarrollo

| | Desarrollo | Producción |
|---|---|---|
| Código | montado del host | dentro de la imagen |
| MySQL | puerto 3310 abierto | sin puerto |
| HTTPS | no | sí, vía `main_nginx` |
| `APP_DEBUG` | `true` | `false` |
| Config y rutas | sin cachear | cacheadas al arrancar |
| Logs | archivo | `stderr` → `docker compose logs` |
| Credenciales | con valores por defecto | obligatorias |

## Pendientes conocidos

- **Sin correo.** Recuperar contraseña y verificación siguen apagados
  (`FUNCIONES.correo = false`). Si pierdes la contraseña hay que cambiarla por
  consola. Se activa cuando haya un proveedor SMTP.
- **Sin copias automáticas.** El comando está arriba; el cron hay que ponerlo.
- **Un solo servidor**, compartido con tus demás apps. Si se cae, se cae todo.
