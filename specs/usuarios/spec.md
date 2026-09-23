# Spec — Usuarios y roles

**Módulo:** Autenticación, perfil y control de acceso
**Origen:** [`sistema_gastos_personales.md`](../sistema_gastos_personales.md) §2 (Usuarios y roles), §3 Módulo 1, §5 (Seguridad), §8 (`users`), §10 (regla 6), §15 (API)
**Estado:** propuesto
**Stack:** Laravel 13 + MySQL 8.4 (API) · Ionic 9 + Angular 22 + Capacitor 8 (app móvil)

---

## 1. Objetivo

Permitir que una persona cree su cuenta, inicie sesión desde cualquier
dispositivo y acceda **únicamente** a su propia información financiera.

Este módulo es la base de todos los demás: sin `user_id` en las tablas de
negocio, ninguno de los módulos siguientes puede aislar datos correctamente. Por
eso va primero.

## 2. Alcance

### Dentro

- Registro con correo y contraseña.
- Inicio y cierre de sesión mediante tokens (móvil y web).
- Verificación de correo electrónico.
- Recuperación y cambio de contraseña.
- Perfil del usuario y sus preferencias.
- Listado y revocación de sesiones/dispositivos.
- Eliminación de cuenta.
- Rol `admin` a nivel de dato y middleware de autorización.
- Aislamiento por usuario en todas las consultas.

### Fuera (por ahora)

- Interfaz del panel de administración. El documento lo marca como innecesario
  en una primera versión personal (§2.2); aquí solo se deja el rol y la puerta
  de autorización preparados.
- Inicio de sesión con proveedores externos (Google, Apple). Se contempla en el
  diseño pero no se implementa.
- Autenticación biométrica y 2FA.

## 3. Roles

| Rol | Descripción | Cómo se asigna |
|---|---|---|
| `user` | Propietario de su información financiera. Rol por defecto de todo registro. | Automático al registrarse |
| `admin` | Administración de la plataforma. | Manualmente en base de datos o por comando de consola |

### Permisos del rol `user`

Sobre **sus propios recursos** únicamente: gestionar su perfil, crear cuentas
financieras, registrar ingresos/gastos/transferencias, crear categorías, definir
presupuestos, crear metas de ahorro, consultar reportes, configurar
recordatorios y exportar sus datos.

### Permisos del rol `admin`

Gestionar usuarios (listar, suspender, eliminar), consultar métricas generales
**anonimizadas**, gestionar configuración global, administrar catálogos
predeterminados y revisar errores y actividad técnica.

Un `admin` **no** puede leer los movimientos financieros de otro usuario. Las
métricas que consulta son agregadas y sin datos personales. Esta restricción es
deliberada y debe sobrevivir a futuras ampliaciones.

## 4. Modelo de datos

### Tabla `users` (extiende la de Laravel)

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `name` | string(120) | |
| `email` | string(180) unique | |
| `email_verified_at` | timestamp null | |
| `password` | string | hash bcrypt |
| `role` | enum(`user`,`admin`) | por defecto `user` |
| `avatar_path` | string null | ruta en disco/S3 |
| `default_currency` | char(3) | ISO 4217, por defecto `COP` |
| `country` | char(2) null | ISO 3166-1 alfa-2 |
| `timezone` | string(64) | por defecto `America/Bogota` |
| `locale` | string(5) | por defecto `es` |
| `theme` | enum(`system`,`light`,`dark`) | por defecto `system` |
| `notification_preferences` | json | mapa `tipo => bool` |
| `status` | enum(`active`,`suspended`) | por defecto `active` |
| `last_login_at` | timestamp null | |
| `created_at` / `updated_at` | timestamps | |
| `deleted_at` | timestamp null | borrado lógico |

`personal_access_tokens` la aporta Laravel Sanctum sin cambios.

### Decisiones

- **`role` como columna, no tabla de roles.** Solo hay dos roles y ninguno
  compone permisos. Una tabla de roles/permisos sería complejidad sin uso. Si
  aparece un tercer rol con permisos granulares, se migra a `spatie/laravel-permission`.
- **Borrado lógico.** `DELETE /api/user` marca `deleted_at`, revoca todos los
  tokens y programa el borrado físico. Protege contra el borrado accidental de
  años de historial financiero.
- **`notification_preferences` en JSON.** Los tipos de notificación crecerán
  (§14 del documento); una columna por tipo obligaría a migrar cada vez.

## 5. Autenticación

**Laravel Sanctum en modo token personal**, no cookies de sesión.

Razón: el cliente principal es una app Capacitor cuyo origen es
`capacitor://localhost` o `http://localhost`. Las cookies `SameSite` no
funcionan de forma fiable en ese contexto, y los tokens sirven igual a la app y
a un futuro cliente web.

- El token viaja en `Authorization: Bearer <token>`.
- Cada token guarda un nombre de dispositivo para poder listarlo y revocarlo.
- Expiración: 30 días de inactividad, renovable de forma transparente.
- En el dispositivo, el token se guarda en el almacenamiento seguro
  (`@capacitor/preferences`, respaldado por Keychain/KeyStore), nunca en
  `localStorage`.

## 6. API

Todas las rutas cuelgan de `/api`. Las marcadas con 🔒 requieren token.

### Autenticación

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/auth/register` | Crea la cuenta, dispara el correo de verificación y devuelve token |
| POST | `/auth/login` | Devuelve token + usuario |
| POST | `/auth/logout` | 🔒 Revoca el token actual |
| POST | `/auth/forgot-password` | Envía enlace de recuperación |
| POST | `/auth/reset-password` | Establece contraseña nueva con el token del correo |
| POST | `/auth/email/verify/{id}/{hash}` | Marca el correo como verificado |
| POST | `/auth/email/resend` | 🔒 Reenvía el correo de verificación |

### Perfil

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/user` | 🔒 Usuario autenticado con sus preferencias |
| PUT | `/user` | 🔒 Actualiza nombre, moneda, país, zona horaria, idioma, tema |
| PUT | `/user/password` | 🔒 Cambia contraseña (exige la actual) |
| POST | `/user/avatar` | 🔒 Sube foto de perfil |
| PUT | `/user/notifications` | 🔒 Actualiza preferencias de notificación |
| DELETE | `/user` | 🔒 Elimina la cuenta (exige contraseña) |

### Sesiones

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/user/sessions` | 🔒 Tokens activos: dispositivo, última actividad |
| DELETE | `/user/sessions/{id}` | 🔒 Revoca un dispositivo |
| DELETE | `/user/sessions` | 🔒 Cierra sesión en todos los demás dispositivos |

### Administración

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/users` | 🔒 admin — listado paginado |
| PUT | `/admin/users/{id}/status` | 🔒 admin — activar/suspender |
| GET | `/admin/metrics` | 🔒 admin — métricas agregadas anonimizadas |

### Formato de respuesta

Éxito:

```json
{ "data": { "id": 1, "name": "Oscar", "email": "…", "role": "user" } }
```

Error de validación (422):

```json
{ "message": "Los datos no son válidos.", "errors": { "email": ["El correo ya está registrado."] } }
```

## 7. Reglas de validación

| Campo | Regla |
|---|---|
| `name` | requerido, 2–120 caracteres |
| `email` | requerido, formato válido, único, máx. 180 |
| `password` | requerido, mín. 8, con mayúscula, minúscula y número; confirmada; no comprometida (`Password::uncompromised()`) |
| `default_currency` | ISO 4217 de la lista soportada |
| `timezone` | zona horaria válida de PHP |
| `theme` | uno de `system`, `light`, `dark` |

## 8. Seguridad

Derivado de §5 del documento origen.

- Contraseñas con hash bcrypt (por defecto de Laravel). Nunca en logs ni respuestas.
- HTTPS obligatorio en producción; `usesCleartextTraffic` solo en desarrollo.
- **Aislamiento por usuario:** todo modelo de negocio lleva `user_id` y se
  consulta a través de un *global scope* que filtra por el usuario autenticado.
  No se confía en que cada consulta recuerde el `where`.
- Los identificadores en la URL nunca bastan: `GET /api/gastos/{id}` de otro
  usuario devuelve **404**, no 403, para no revelar existencia.
- *Rate limiting*: 5 intentos por minuto en `login`, `forgot-password` y
  `register`, por IP y por correo.
- La respuesta de `forgot-password` es idéntica exista o no el correo, para no
  filtrar qué correos están registrados.
- Al cambiar la contraseña se revocan todos los tokens salvo el actual.
- Validación en backend siempre; la del frontend es solo comodidad.

## 9. Interfaz de la app

Pantallas nuevas:

1. **Bienvenida** — logo, "Iniciar sesión" y "Crear cuenta".
2. **Registro** — nombre, correo, contraseña con indicador de fortaleza.
3. **Inicio de sesión** — correo, contraseña, "¿Olvidaste tu contraseña?".
4. **Recuperar contraseña** — correo y confirmación de envío.
5. **Verificación de correo** — aviso con opción de reenviar.
6. **Perfil** — datos, avatar y acceso a las demás secciones.
7. **Preferencias** — moneda, zona horaria, idioma, tema.
8. **Seguridad** — cambiar contraseña, sesiones activas, eliminar cuenta.

Comportamiento:

- Un `AuthGuard` protege las rutas privadas y redirige a bienvenida.
- Un interceptor HTTP añade el `Bearer` y, ante un 401, limpia la sesión y
  redirige.
- La sesión sobrevive al cierre de la app: el token se lee del almacenamiento
  seguro al arrancar.
- Los errores de red se distinguen de los de credenciales en el mensaje.

## 10. Criterios de aceptación

1. Un visitante se registra y queda autenticado, con `role = user`.
2. No se puede registrar dos veces el mismo correo (422 con mensaje claro).
3. Con credenciales incorrectas la respuesta es 401 y no revela si el correo existe.
4. Tras seis intentos fallidos en un minuto, la respuesta es 429.
5. Una petición sin token a una ruta protegida devuelve 401.
6. El usuario A no puede leer, editar ni borrar recursos del usuario B: 404.
7. Al cerrar sesión, el token deja de funcionar de inmediato.
8. Al cambiar la contraseña, los demás dispositivos quedan desconectados.
9. El usuario ve sus sesiones activas y puede revocar una concreta.
10. Al eliminar la cuenta, todos los tokens se revocan y el login deja de funcionar.
11. Al reiniciar la app, la sesión sigue abierta sin volver a escribir credenciales.
12. Un `user` que llama a `/api/admin/*` recibe 403.
13. La contraseña no aparece en ninguna respuesta ni en los logs.

## 11. Migración de lo existente

Hoy la tabla `gastos` no tiene dueño y la API es pública. Al implementar este
módulo:

1. Añadir `user_id` a `gastos` con clave foránea.
2. Asignar los registros existentes al primer usuario creado (son datos de prueba).
3. Mover `/api/gastos` detrás del middleware `auth:sanctum`.
4. Aplicar el *global scope* de propietario al modelo `Gasto`.

## 12. Riesgos y decisiones abiertas

- **Correo saliente.** Verificación y recuperación necesitan un proveedor SMTP
  real. En desarrollo se usa el driver `log`; falta decidir el de producción
  (Mailgun, Resend, SES).
- **Almacenamiento del avatar.** Disco local en el contenedor se pierde al
  redesplegar. Conviene S3 o un volumen persistente desde el principio.
- **Verificación obligatoria.** Está sin decidir si un correo no verificado
  bloquea el uso de la app o solo muestra un aviso. La propuesta es **no
  bloquear** en la primera versión, para no frenar el propio uso personal.
