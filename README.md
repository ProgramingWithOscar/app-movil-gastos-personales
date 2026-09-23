# Gastos Personales

Aplicación móvil de finanzas personales.

- **`backend/`** — API REST con Laravel 13 + MySQL 8.4, dockerizada y desplegable por separado.
- **`frontend/`** — App móvil Ionic 9 + Angular 22 + Capacitor 8 (Android / iOS).

La app y el backend son independientes: la app consume la API por HTTP, así que
el backend puede vivir en cualquier servidor.

---

## Backend

Stack Docker con tres servicios: `db` (MySQL 8.4), `backend` (php-fpm) y
`nginx-backend`.

```bash
cp .env.example .env
docker compose up -d --build
```

- API: <http://localhost:8200/api>
- MySQL publicado en el host: `127.0.0.1:3310`

Las migraciones se ejecutan solas al arrancar el contenedor. Para desarrollo sin
Docker: `cd backend && composer install && php artisan serve`.

### Endpoints

Todas las rutas cuelgan de `/api`. Las marcadas con 🔒 requieren
`Authorization: Bearer <token>`.

#### Autenticación

| Método | Ruta | Descripción |
|---|---|---|
| POST | `/auth/register` | Crea la cuenta y devuelve token |
| POST | `/auth/login` | Devuelve token + usuario |
| POST | `/auth/logout` | 🔒 Revoca el token actual |

Registro y login están limitados a 5 intentos por minuto, por IP y por correo.

#### Perfil y sesiones

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/user` | 🔒 Usuario autenticado |
| PUT | `/user` | 🔒 Nombre, moneda, país, zona horaria, idioma, tema |
| PUT | `/user/password` | 🔒 Cambia la contraseña y cierra los demás dispositivos |
| PUT | `/user/notifications` | 🔒 Preferencias de notificación |
| POST | `/user/avatar` | 🔒 Foto de perfil (imagen, máx. 2 MB) |
| DELETE | `/user` | 🔒 Elimina la cuenta (exige contraseña) |
| GET | `/user/sessions` | 🔒 Dispositivos con sesión abierta |
| DELETE | `/user/sessions/{id}` | 🔒 Cierra una sesión |
| DELETE | `/user/sessions` | 🔒 Cierra todas menos la actual |

#### Dashboard

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/dashboard` | 🔒 Todo lo que pinta la pantalla principal |

Parámetros:

| Parámetro | Valores | Por defecto |
|---|---|---|
| `periodo` | `dia`, `semana`, `mes`, `anio`, `personalizado` | `mes` |
| `desde` / `hasta` | `YYYY-MM-DD`, obligatorios si `periodo=personalizado` | — |
| `movimientos` | 1 a 50 | 10 |

```bash
curl "http://localhost:8200/api/dashboard?periodo=mes&movimientos=20" \
  -H "Authorization: Bearer $TOKEN" -H "Accept: application/json"
```

```json
{
  "data": {
    "tiene_movimientos": true,
    "periodo": { "tipo": "mes", "desde": "2026-08-01", "hasta": "2026-08-31",
                 "dias": 31, "dias_transcurridos": 23, "en_curso": true },
    "gastos": {
      "total": 198000, "movimientos": 3,
      "promedio_diario": 8608.7, "proyeccion_fin_periodo": 266869.7,
      "por_categoria": [
        { "categoria": "servicios", "total": 95000, "cantidad": 1, "porcentaje": 48 }
      ],
      "categoria_principal": { "categoria": "servicios", "total": 95000,
                               "cantidad": 1, "porcentaje": 48 }
    },
    "comparacion": {
      "periodo_anterior": { "desde": "2026-07-01", "hasta": "2026-07-31", "total": 0 },
      "diferencia": 198000,
      "variacion_porcentual": null,
      "tendencia": "sin_referencia"
    },
    "movimientos_recientes": [
      { "id": 1, "descripcion": "Mercado", "monto": 85000,
        "categoria": "alimentacion", "fecha": "2026-08-22" }
    ]
  }
}
```

Detalles que importan al consumirlo:

- Los rangos se calculan en la **zona horaria del usuario**, no la del servidor.
- `promedio_diario` divide entre los días **transcurridos**, no entre los del
  período: en un mes en curso, dividir entre 31 el día 5 falsea el promedio.
- `variacion_porcentual` es **`null`** cuando el período anterior no tuvo gastos.
  No hay referencia con la que comparar; no lo interpretes como 0 ni como 100.
- `tendencia` viene resuelta desde el servidor (`sube`, `baja`, `igual`,
  `sin_referencia`) para que el cliente no decida el signo. **En gastos, `baja`
  es una buena noticia.**
- `tiene_movimientos` distingue "nunca has registrado nada" de "este período
  está vacío": son estados vacíos con mensajes distintos.
- Las claves de módulos futuros (`saldo`, `ingresos`, `presupuestos`, `metas`)
  se añadirán a este mismo objeto. **No asumas que una clave está presente.**

Rendimiento medido con 10 000 movimientos: entre 21 y 33 ms según el período.

#### Gastos

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/gastos` | 🔒 Lista (`?categoria=`, `?desde=`, `?hasta=`, `?limite=`) |
| POST | `/gastos` | 🔒 Crea un gasto |
| GET | `/gastos/{id}` | 🔒 Detalle |
| PUT | `/gastos/{id}` | 🔒 Actualiza |
| DELETE | `/gastos/{id}` | 🔒 Elimina |

```json
{ "descripcion": "Café", "monto": 12500, "categoria": "alimentacion", "fecha": "2026-08-22" }
```

Cada usuario solo ve y modifica lo suyo: un recurso ajeno devuelve **404**, no
403, para no revelar que existe.

#### Presupuestos

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/presupuestos` | 🔒 Lista, ya cruzada con lo gastado |
| POST | `/presupuestos` | 🔒 Crea uno |
| PUT | `/presupuestos/{id}` | 🔒 Actualiza |
| DELETE | `/presupuestos/{id}` | 🔒 Elimina |

```json
{ "categoria": "alimentacion", "monto": 600000, "periodo": "mes", "umbral_alerta": 80 }
```

`categoria` en `null` crea un presupuesto **general**, que cubre todo el gasto
del período. Solo puede haber uno por categoría y período: dos para lo mismo
harían imposible decir cuánto queda.

Cada elemento vuelve evaluado, con `gastado`, `disponible`, `porcentaje`,
`dias_restantes`, `proyeccion` y `estado`:

| Estado | Significado |
|---|---|
| `al_dia` | Por debajo del umbral |
| `cerca` | Superó el umbral de aviso |
| `en_riesgo` | Al ritmo actual se superará antes de terminar el período |
| `superado` | Ya se pasó del límite |

Cada presupuesto se evalúa contra **su** período, no contra el que muestre el
dashboard.

#### Cuentas financieras

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/cuentas` | 🔒 Lista con saldo calculado (`?incluir_archivadas=1`) |
| POST | `/cuentas` | 🔒 Crea |
| GET | `/cuentas/{id}` | 🔒 Detalle |
| PUT | `/cuentas/{id}` | 🔒 Actualiza |
| DELETE | `/cuentas/{id}` | 🔒 Archiva, o borra si nunca tuvo movimientos |
| PUT | `/cuentas/{id}/favorita` | 🔒 La deja preseleccionada al registrar |
| GET | `/cuentas/{id}/movimientos` | 🔒 Historial paginado |

```json
{ "nombre": "Bancolombia", "tipo": "bancaria", "saldo_inicial": 2500000 }
```

Tipos: `efectivo`, `bancaria`, `ahorro`, `corriente`, `billetera`,
`tarjeta_credito`, `tarjeta_debito`, `inversion`, `otra`. El `index` los
devuelve en `tipos`, con icono y color, para no duplicar esa tabla en el cliente.

El `index` incluye además un `resumen`:

```json
{ "saldo_total": 4239000, "deuda": 800000, "patrimonio_neto": 3439000 }
```

Detalles que importan al consumirlo:

- **El saldo no se guarda, se calcula** desde los movimientos en cada lectura
  (`saldo_inicial + ingresos − gastos`). Así no puede desviarse de su historial.
  Medido: 25 ms con 10 000 movimientos.
- **Las tarjetas de crédito no suman al `saldo_total`**: su saldo negativo va a
  `deuda`. Sumarlas daría un número que no es ni lo que tienes ni lo que debes.
- **`DELETE` archiva** si la cuenta tiene movimientos, y solo borra de verdad si
  nunca tuvo ninguno. El mensaje de respuesta dice cuál de las dos ocurrió.
- Una cuenta archivada **conserva su historial** pero deja de sumar y no admite
  movimientos nuevos.
- Solo se permiten cuentas en la moneda principal del usuario: sumar monedas
  distintas sin tasa de cambio no significa nada.

#### Administración

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/admin/users` | 🔒 admin — listado paginado |
| PUT | `/admin/users/{id}/status` | 🔒 admin — activar o suspender |
| GET | `/admin/metrics` | 🔒 admin — métricas agregadas y anonimizadas |

Un administrador **no** puede leer los movimientos de otra persona: solo cifras
agregadas.

### CORS

Los orígenes permitidos se controlan con la variable `CORS_ALLOWED_ORIGINS` en
`docker-compose.yml`. Ya incluye los que necesita la app:

```
http://localhost:8100     # ionic serve
http://localhost          # WebView de Capacitor
capacitor://localhost     # iOS
```

Al desplegar, añade el origen real de la app si la sirves también como web.

---

## App móvil

Requiere **Node 24.15+** (`nvm use --lts`).

```bash
cd frontend
npm install
npm start          # http://localhost:8100 en el navegador
```

### Compilar para Android

```bash
npm run android      # build + sync + abre Android Studio
npm run android:run  # build + sync + instala en el dispositivo/emulador
```

O directamente con Gradle:

```bash
cd android && ./gradlew assembleDebug
# → android/app/build/outputs/apk/debug/app-debug.apk
```

#### Ver cambios en tiempo real (live reload)

No hace falta regenerar el APK en cada cambio. Con el dispositivo o emulador
conectado:

```bash
npm run dev:android
```

Instala la app una vez y luego la WebView carga desde el dev-server: cada cambio
en el HTML, SCSS o TypeScript se refleja al instante, sin recompilar nada.

Solo necesitas volver a generar el APK cuando cambies algo nativo — plugins de
Capacitor, permisos del manifest, iconos o el `.env`:

```bash
npm run apk    # build + sync + gradlew assembleDebug
```

Para iterar solo en la interfaz, lo más rápido sigue siendo el navegador:

```bash
npm start      # http://localhost:8100
```

#### Toolchain en WSL

El JDK y el SDK están instalados en el home del usuario (sin sudo) y exportados
desde `~/.bashrc`:

| | Ruta |
|---|---|
| JDK 21 (Temurin) | `~/.jdks/jdk-21.0.12.1+1` |
| Android SDK | `~/Android/Sdk` (platform-tools, platform 36, build-tools 36) |

Android Studio corre en Windows; WSL solo compila. Para probar el APK en el
emulador de Windows, cópialo a `C:\Users\<usuario>\Downloads` y arrástralo
sobre la ventana del emulador, o instálalo con el `adb` de Windows.

Windows alcanza el backend de WSL por `localhost`, y el emulador alcanza a
Windows por `10.0.2.2`, así que `API_URL_NATIVE=http://10.0.2.2:8200/api`
funciona sin configuración extra.

### Compilar para iOS

```bash
npx cap add ios
npm run ios
```

Solo funciona en macOS con Xcode.

### A qué backend apunta

Un solo archivo: **`frontend/.env`**.

```bash
cd frontend
cp .env.example .env
```

```ini
API_URL=http://localhost:8200/api          # navegador (ionic serve)
API_URL_NATIVE=http://10.0.2.2:8200/api    # app nativa (emulador Android)
```

Para apuntar a producción, cambia esas dos líneas (o `cp .env.production.example .env`)
y vuelve a compilar:

```bash
npm run android:run     # o npm run build:prod
```

`scripts/set-env.mjs` corre solo antes de cada `start` y `build`, y genera
`src/environments/environment.ts` desde el `.env`. Ese archivo generado está en
`.gitignore` — no lo edites a mano.

`GastosService` elige entre `API_URL` y `API_URL_NATIVE` con
`Capacitor.isNativePlatform()`, así que el navegador y el móvil pueden apuntar a
sitios distintos durante el desarrollo y al mismo dominio en producción.

> En un dispositivo físico durante el desarrollo, pon en `API_URL_NATIVE` la IP
> de tu máquina en la red local (por ejemplo `http://192.168.1.20:8200/api`).
> Para producción usa HTTPS: `android:usesCleartextTraffic` está activado solo
> por comodidad en desarrollo y conviene quitarlo antes de publicar.
