# Spec — Cuentas financieras

**Módulo:** 3 · Los lugares donde está el dinero
**Origen:** [`sistema_gastos_personales.md`](../../sistema_gastos_personales.md) §3 Módulo 3, §8 (`accounts`), §10 (reglas 1, 2, 3, 7)
**Estado:** propuesto
**Depende de:** [Usuarios y roles](../usuarios/spec.md) (implementado)
**Desbloquea:** saldo total, dinero disponible y evolución del saldo en el
[Dashboard](../dashboard/spec.md) (Fase B, pendiente), y el Módulo 6
(transferencias)

---

## 1. Objetivo

Hoy la app sabe **cuánto se gastó**, pero no **cuánto hay**. Los movimientos
existen en el vacío: no salen de ningún sitio ni entran a ninguno.

Este módulo introduce las cuentas y ata cada movimiento a una, para poder
responder la primera mitad de la pregunta que define el producto (§20):

> **¿Cuánto dinero tengo**, cuánto he gastado, en qué lo he gastado y cuánto
> puedo gastar sin superar mi presupuesto?

## 2. Alcance

### Dentro

- Crear, editar y archivar cuentas.
- Los nueve tipos que enumera el documento.
- Saldo inicial y saldo actual calculado.
- Color e icono, cuenta favorita.
- Historial de movimientos por cuenta.
- Atar cada movimiento a una cuenta, migrando los que ya existen.
- Saldo total y dinero disponible en el dashboard.

### Fuera

- **Transferencias entre cuentas.** El documento las lista como funcionalidad de
  este módulo, pero les dedica el Módulo 6 entero. Aquí se deja el terreno
  preparado —`movimientos.cuenta_id` y el tipo `transferencia` en el enum— y se
  implementan allí. Una transferencia toca dos cuentas y tiene reglas propias
  (no debe contarse como gasto ni como ingreso, §10 regla 4); mezclarla aquí
  haría este módulo el doble de grande y el doble de arriesgado.
- Conversión entre monedas. Se guarda la moneda de cada cuenta, pero sin tasas
  de cambio: sumar cuentas en monedas distintas sin conversión sería mentir. Ver
  §7.
- Conciliación bancaria e integración con bancos (§5, escalabilidad).

## 3. Modelo de datos

### Tabla `cuentas`

| Columna | Tipo | Notas |
|---|---|---|
| `id` | bigint PK | |
| `user_id` | FK users | cascade on delete |
| `nombre` | string(80) | |
| `tipo` | string(24) | enum `TipoCuenta` |
| `moneda` | char(3) | ISO 4217, por defecto la del usuario |
| `saldo_inicial` | decimal(14,2) | puede ser negativo en tarjetas de crédito |
| `color` | string(9) | hex |
| `icono` | string(40) | nombre de ionicon |
| `favorita` | boolean | por defecto `false` |
| `archivada` | boolean | por defecto `false` |
| `orden` | unsigned smallint | para ordenar a mano |
| `created_at` / `updated_at` | timestamps | |

Índices: `(user_id, archivada)` y único `(user_id, nombre)`.

### Cambio en `movimientos`

Se añade `cuenta_id` (FK a `cuentas`, `restrictOnDelete`) e índice
`(cuenta_id, fecha)`.

### Tipos de cuenta

Los nueve del documento: `efectivo`, `bancaria`, `ahorro`, `corriente`,
`billetera`, `tarjeta_credito`, `tarjeta_debito`, `inversion`, `otra`.

Cada tipo trae icono y color por defecto, para que crear una cuenta sean dos
campos y no seis.

## 4. Las tres decisiones que definen el módulo

### 4.1 El saldo se calcula, no se guarda

```
saldo_actual = saldo_inicial + Σ(ingresos) − Σ(gastos)
```

El documento lo pide dos veces: "saldo actual **calculado**" (§3) y "el saldo
debe poder recalcularse a partir de los movimientos para garantizar integridad"
(§10, regla 7).

Guardar el saldo en una columna e irlo sumando es más rápido de leer, pero
introduce la peor clase de error en una app de dinero: **un saldo que no cuadra
con su propio historial**. Basta un fallo a medias, un borrado que no reste o
dos peticiones simultáneas para que el número mostrado deje de tener respaldo, y
nadie lo nota hasta que ya no se sabe cuál era el correcto.

Se calcula con una agregación indexada, como ya se hace en el dashboard, donde
10 000 movimientos se resuelven en 30 ms. **Si algún día no rinde**, la salida
es una columna cacheada que se recalcula desde los movimientos —nunca una que
sea la única fuente de verdad—. No se implementa hasta medirlo.

### 4.2 Las cuentas se archivan, no se borran

Borrar una cuenta dejaría huérfanos sus movimientos, y con ellos el historial
que da sentido a los totales de meses pasados. Una cuenta archivada:

- No aparece al crear un movimiento.
- No suma en el saldo total.
- Conserva su historial y sigue apareciendo en los reportes del período en que
  tuvo actividad.

La clave foránea es `restrictOnDelete` a propósito: si alguien intenta borrar una
cuenta con movimientos, la base de datos lo impide. Preferimos un error explícito
a un borrado en cascada que se lleve por delante meses de datos.

### 4.3 Las tarjetas de crédito no son dinero disponible

Una tarjeta de crédito con saldo −$500.000 no significa que tengas menos dinero:
significa que **debes** medio millón. Sumarla al saldo total daría un número que
no es ni lo que tienes ni lo que debes.

Por eso se separan dos cifras:

| Cifra | Qué incluye |
|---|---|
| **Saldo total** | Cuentas de dinero propio (todas menos `tarjeta_credito`) |
| **Deuda** | Suma de saldos negativos de tarjetas de crédito |
| **Patrimonio neto** | Saldo total − deuda |

El dashboard muestra el saldo total; la deuda aparece junto a él solo si hay
tarjetas. Es la diferencia entre una app que suma columnas y una que entiende de
dinero.

## 5. Migración de lo que ya existe

Hoy hay movimientos sin cuenta. El orden importa, porque una columna obligatoria
sobre datos que no la tienen rompe la migración:

1. Añadir `cuenta_id` **nullable**.
2. Crear para cada usuario con movimientos una cuenta `Efectivo` con saldo
   inicial 0.
3. Asignar todos sus movimientos a esa cuenta.
4. Hacer la columna obligatoria.

El nombre "Efectivo" es una suposición honesta: no sabemos de dónde salió ese
dinero, y efectivo es el caso más común. La persona puede renombrarla.

## 6. API

| Método | Ruta | Descripción |
|---|---|---|
| GET | `/api/cuentas` | Lista con saldo calculado (`?incluir_archivadas=1`) |
| POST | `/api/cuentas` | Crea |
| GET | `/api/cuentas/{id}` | Detalle con resumen del mes |
| PUT | `/api/cuentas/{id}` | Actualiza |
| DELETE | `/api/cuentas/{id}` | Archiva (no borra) |
| PUT | `/api/cuentas/{id}/favorita` | Marca como favorita |
| GET | `/api/cuentas/{id}/movimientos` | Historial paginado |

Respuesta de la lista:

```json
{
  "data": [
    {
      "id": 1, "nombre": "Bancolombia", "tipo": "bancaria",
      "moneda": "COP", "saldo_inicial": 500000, "saldo_actual": 2350000,
      "color": "#0284c7", "icono": "card-outline",
      "favorita": true, "archivada": false, "movimientos": 42
    }
  ],
  "resumen": { "saldo_total": 2350000, "deuda": 500000, "patrimonio_neto": 1850000 }
}
```

`POST /api/movimientos` pasa a exigir `cuenta_id`, y valida que la cuenta sea del
usuario y no esté archivada.

## 7. Monedas

Cada cuenta guarda su moneda, pero **el saldo total solo suma cuentas en la
moneda principal del usuario**. Las de otra moneda se muestran con su saldo
propio y quedan fuera del total, con un aviso.

Sumar monedas distintas sin tasa de cambio produce un número sin significado.
Con tasa, produce uno que envejece. Hasta que exista el soporte multi-moneda que
el documento sitúa en escalabilidad (§5), no sumar es la única respuesta honesta.

En la primera versión se puede restringir la creación a la moneda principal y
dejar el campo preparado.

## 8. Interfaz

### Pantalla de cuentas

Reemplaza el marcador de la pestaña **Movimientos** o entra como sección nueva
—se decide en §11—.

1. **Cabecera** con saldo total y, si hay tarjetas, la deuda.
2. **Lista de cuentas**: icono con su color, nombre, tipo y saldo. La favorita
   primero, luego por `orden`.
3. **Archivadas** en una sección plegada al final.
4. **Botón de crear**.

### Detalle de cuenta

Saldo, ingresos y gastos del mes, y el historial de movimientos con scroll.

### Formulario

Nombre, tipo (con icono y color por defecto según el tipo), saldo inicial y
moneda. Color e icono editables después.

### Efecto en el resto de la app

- El formulario de nuevo movimiento gana un selector de cuenta, con la favorita
  preseleccionada.
- La lista de movimientos muestra a qué cuenta pertenece cada uno.
- El dashboard estrena la tarjeta de saldo total.

## 9. Criterios de aceptación

1. Un usuario puede crear una cuenta con nombre, tipo y saldo inicial.
2. El saldo actual refleja el saldo inicial más los ingresos menos los gastos.
3. Registrar un gasto en una cuenta baja su saldo; un ingreso lo sube.
4. Eliminar un movimiento devuelve el saldo a su valor anterior.
5. El saldo recalculado desde cero coincide siempre con el mostrado.
6. Una cuenta archivada no aparece al crear un movimiento ni suma en el total.
7. Una cuenta archivada conserva su historial.
8. Intentar borrar una cuenta con movimientos falla de forma explícita.
9. Nadie ve ni usa cuentas de otro usuario: 404, no 403.
10. Una tarjeta de crédito con saldo negativo no resta del saldo total: aparece
    como deuda.
11. No se pueden crear dos cuentas con el mismo nombre para el mismo usuario.
12. Un movimiento sin `cuenta_id` es rechazado con 422.
13. Los movimientos que existían antes de este módulo quedan asignados a una
    cuenta y ningún total cambia tras la migración.

El 13 es el que hay que vigilar: una migración que altere totales históricos
destruye la confianza en todos los números anteriores.

## 10. Riesgos

1. **Que el saldo deje de cuadrar.** Mitigado calculándolo siempre desde los
   movimientos y con el criterio 5 como prueba.
2. **La migración de datos existentes.** Es irreversible en la práctica. Debe
   probarse contra una copia antes de ejecutarla, y el criterio 13 verificarse
   antes y después.
3. **Volver obligatorio un campo que antes no existía.** `cuenta_id` rompe
   cualquier cliente viejo que siga enviando movimientos sin cuenta. Como la app
   es nuestra y se despliega a la vez, es asumible; conviene devolver un mensaje
   de error claro y no un fallo genérico.

## 11. Decisiones abiertas

1. **¿Dónde vive la pantalla de cuentas?** La barra tiene cinco huecos y los
   cinco están ocupados (Inicio, Movimientos, +, Presupuestos, Perfil). Opciones:
   entrar como sección dentro de Perfil, sustituir Movimientos, o poner un acceso
   desde la tarjeta de saldo del dashboard. *Propuesta: acceso desde la tarjeta
   de saldo, y también dentro de Perfil.*
2. **¿Se permite crear cuentas en otra moneda desde el principio?**
   *Propuesta: no. Guardar el campo, pero restringirlo a la moneda principal
   hasta que exista conversión.*
3. **¿El saldo inicial puede editarse después de creada la cuenta?** Cambiarlo
   reescribe el saldo actual y con él el histórico percibido. *Propuesta: sí,
   pero avisando de que afecta a todos los saldos mostrados.*
