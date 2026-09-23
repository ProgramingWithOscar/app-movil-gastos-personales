# Tareas — Usuarios y roles

Plan de implementación de [`spec.md`](./spec.md).
Las tareas están ordenadas: cada bloque depende del anterior.

Leyenda: `[ ]` pendiente · `[~]` en curso · `[x]` hecho

---

## Bloque 1 · Base del backend

- [x] **1.1** Instalar Sanctum: `php artisan install:api`, revisar que registre
      `routes/api.php` sin pisar el `bootstrap/app.php` actual.
- [x] **1.2** Migración `add_profile_fields_to_users_table`: `role`,
      `avatar_path`, `default_currency`, `country`, `timezone`, `locale`,
      `theme`, `notification_preferences`, `status`, `last_login_at`,
      `deleted_at`.
- [x] **1.3** Modelo `User`: `HasApiTokens`, `SoftDeletes`, `$fillable`,
      `$hidden` (password, remember_token), casts (`notification_preferences`
      → array, `email_verified_at` → datetime).
- [x] **1.4** Enums `UserRole` y `UserStatus` en `app/Enums/`, casteados en el modelo.
- [x] **1.5** `UserFactory` actualizada + seeder con un usuario de desarrollo.

**Listo cuando:** `php artisan migrate:fresh --seed` corre limpio y
`User::factory()->create()` produce un usuario con `role = user`. ✅

## Bloque 2 · Registro e inicio de sesión

- [x] **2.1** `RegisterRequest`, `LoginRequest` con las reglas de la spec §7
      (incluida `Password::uncompromised()`).
- [x] **2.2** `AuthController@register`: crea usuario, dispara `Registered`,
      devuelve token + usuario (201).
- [x] **2.3** `AuthController@login`: valida credenciales, rechaza usuarios
      `suspended`, actualiza `last_login_at`, nombra el token con el
      dispositivo recibido.
- [x] **2.4** `AuthController@logout`: revoca el token actual (204).
- [x] **2.5** `UserResource` para no exponer nunca `password` ni `remember_token`.
- [x] **2.6** Rate limiting: 5/min por IP y por correo en `register`, `login`
      y `forgot-password`.
- [x] **2.7** Tests: registro correcto, correo duplicado, credenciales
      inválidas, usuario suspendido, límite de intentos, logout invalida token.

**Listo cuando:** se puede registrar, iniciar y cerrar sesión por `curl` y los
tests pasan. ✅ 16 tests, 57 aserciones.

## Bloque 3 · Correo: verificación y recuperación — **APLAZADO**

> Sin proveedor SMTP por ahora. Las pantallas existen pero la opción está
> apagada con `FUNCIONES.correo = false` en el frontend.

- [ ] **3.1** `User implements MustVerifyEmail`; configurar `MAIL_*` con driver
      `log` en desarrollo.
- [ ] **3.2** Rutas de verificación con URL firmada y `POST /auth/email/resend`.
- [ ] **3.3** `POST /auth/forgot-password` y `POST /auth/reset-password` con
      respuesta idéntica exista o no el correo.
- [ ] **3.4** Traducir al español las plantillas de correo.
- [ ] **3.5** Al restablecer la contraseña, revocar todos los tokens del usuario.
- [ ] **3.6** Tests con `Notification::fake()`: se envía la notificación, el
      enlace firmado verifica, un enlace manipulado falla, el reset revoca tokens.

**Listo cuando:** el flujo completo funciona contra el driver `log`.

## Bloque 4 · Perfil y sesiones

- [x] **4.1** `UserController@show` / `@update` / `@update` (nombre, moneda, país, zona
      horaria, idioma, tema).
- [x] **4.2** `UserController@updatePassword`: exige la actual, revoca los demás tokens.
- [x] **4.3** `UserController@updateNotifications` sobre el JSON de preferencias.
- [x] **4.4** `POST /user/avatar`: validar imagen ≤ 2 MB, guardar en disco
      `public`, devolver URL.
- [x] **4.5** `SessionController`: listar tokens, revocar uno, revocar los demás.
- [x] **4.6** `DELETE /user`: exige contraseña, borrado lógico, revoca todo.
- [~] **4.7** Tests de cada endpoint, incluido que el cambio de contraseña
      desconecta los otros dispositivos.

**Listo cuando:** los criterios 7 a 11 de la spec §10 se cumplen.

## Bloque 5 · Autorización y aislamiento

- [x] **5.1** Middleware `EnsureUserIsAdmin` registrado como alias `admin`.
- [x] **5.2** Rutas `/api/admin/*` protegidas: listado de usuarios, cambio de
      estado, métricas agregadas anonimizadas.
- [x] **5.3** Trait `BelongsToUser` con *global scope* por usuario autenticado y
      relleno automático de `user_id` al crear.
- [x] **5.4** Migración: `user_id` en `gastos` + clave foránea; asignar los
      registros existentes al primer usuario.
- [x] **5.5** Aplicar el trait a `Gasto` y mover `/api/gastos` detrás de
      `auth:sanctum`.
- [x] **5.6** `Route::missing()` / `findOrFail` de forma que el recurso ajeno
      devuelva 404, nunca 403.
- [x] **5.7** Tests: usuario A no ve ni toca nada de B; `user` contra
      `/api/admin/*` recibe 403.

**Listo cuando:** los criterios 5, 6 y 12 de la spec §10 se cumplen. ✅

## Bloque 6 · App: capa de autenticación

- [x] **6.1** Instalar `@capacitor/preferences` y crear `TokenStorage` que
      guarde el token en almacenamiento seguro.
- [x] **6.2** `AuthService` con signals: `usuario`, `autenticado`, y métodos
      `registrar`, `iniciarSesion`, `cerrarSesion`, `restaurarSesion`.
- [x] **6.3** `authInterceptor`: añade `Authorization: Bearer`; ante 401 limpia
      la sesión y redirige a bienvenida.
- [x] **6.4** `authGuard` y `guestGuard` sobre las rutas.
- [x] **6.5** `APP_INITIALIZER` que llame a `restaurarSesion()` antes de pintar,
      con splash mientras resuelve.
- [x] **6.6** Modelos TypeScript `Usuario`, `Credenciales`, `RespuestaAuth`.

**Listo cuando:** al reiniciar la app la sesión sigue abierta y una ruta privada
sin token redirige. ✅ Verificado en el emulador.

## Bloque 7 · App: pantallas

- [x] **7.1** Bienvenida.
- [x] **7.2** Registro, con indicador de fortaleza de contraseña.
- [x] **7.3** Inicio de sesión.
- [~] **7.4** Recuperar contraseña. Pantalla lista; falta el endpoint del Bloque 3.
- [~] **7.5** Aviso de correo sin verificar, con botón de reenvío. Pantalla lista; falta el endpoint del Bloque 3.
- [x] **7.6** Perfil: datos y avatar. **Bloqueado por el Bloque 4.**
- [x] **7.7** (Bloqueado por el Bloque 4) Preferencias: moneda, zona horaria, idioma, tema (aplicar el tema
      de inmediato).
- [x] **7.8** (Bloqueado por el Bloque 4) Seguridad: cambiar contraseña, sesiones activas, eliminar cuenta
      con doble confirmación.
- [x] **7.9** Mensajes de error distintos para fallo de red y credenciales
      incorrectas.

**Listo cuando:** el flujo completo se puede recorrer en el emulador.

## Bloque 8 · Cierre

- [ ] **8.1** Añadir a `CORS_ALLOWED_ORIGINS` los orígenes reales de la app
      (incluido `http://10.0.2.2:8100` mientras se use live reload).
- [ ] **8.2** Decidir proveedor SMTP de producción y documentarlo.
- [ ] **8.3** Decidir almacenamiento del avatar (S3 o volumen persistente).
- [ ] **8.4** Comando `php artisan user:make-admin {email}`.
- [ ] **8.5** Repasar los 13 criterios de aceptación de la spec §10 uno por uno.
- [ ] **8.6** Actualizar el README con los endpoints de autenticación.

---

## Fuera de este alcance

Quedan explícitamente para más adelante, tal como indica la spec §2:

- Interfaz del panel de administración.
- Inicio de sesión con Google o Apple.
- Autenticación biométrica y 2FA.
- Cuentas compartidas.

## Decisiones que hay que tomar antes de terminar

1. ¿El correo sin verificar bloquea el uso de la app? *Propuesta: no, solo aviso.*
2. ¿Proveedor SMTP de producción?
3. ¿Avatar en S3 o en volumen persistente?
