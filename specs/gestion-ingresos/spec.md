# Spec — Gestión de ingresos

**Módulo:** 4 · El dinero que entra
**Origen:** [`sistema_gastos_personales.md`](../../sistema_gastos_personales.md) §Módulo 4, §8 (`transactions`), §10 (reglas 4 y 5)
**Estado:** parcialmente implementado — este spec cierra lo que falta
**Depende de:** [Cuentas financieras](../cuentas/spec.md) (implementado)
**Desbloquea:** el Módulo 5 (gastos) hereda todo lo que se haga aquí, porque
comparten tabla

---

## 1. Objetivo

Los ingresos ya se registran. Lo que falta no es la función, es lo que la
sostiene: **las categorías viven solo en el teléfono**, no hay forma de
corregir un ingreso mal capturado, y el documento pide dos campos que no
existen.

Este módulo convierte lo que hoy funciona "de facto" en algo que aguanta:
categorías que el servidor conoce y valida, edición, anulación y etiquetas.

## 2. Punto de partida

Conviene ser exacto sobre qué hay ya, porque casi todo el valor de este módulo
está en los huecos, no en lo nuevo.

### Ya funciona

Se implementó de camino a la [Fase B del dashboard](../dashboard/task.md):

- La tabla `movimientos` con `tipo` (`gasto` / `ingreso`) y el importe siempre
  positivo. El signo lo pone el tipo.
- `POST`, `GET`, `PUT` y `DELETE /api/movimientos`.
- Cada ingreso va atado a una cuenta activa y suma a su saldo.
- El dashboard separa ingresos de gastos: total, dona, categorías, comparación
  con el período anterior y tasa de ahorro.
- La hoja de acciones registra ingresos con su propio catálogo de categorías.

### Lo que no

| Lo que pide el documento | Estado |
|---|---|
| Registrar ingreso | hecho |
| Asociar cuenta | hecho |
| Añadir descripción | hecho |
| Asignar categoría | **a medias** — ver §3 |
| Editar ingreso | **no** — la API lo permite, la app no lo ofrece |
| Eliminar **o anular** | solo eliminar |
| Etiquetas | no |
| Estado | no |
| Adjuntar comprobante | fuera: el propio documento lo pone "en el futuro" |
| Ingresos recurrentes | fuera: es el Módulo 9 |

## 3. El problema de fondo: las categorías no existen en el servidor

Hoy el catálogo de categorías está escrito en
[`home.page.ts`](../../frontend/src/app/home/home.page.ts), y el backend acepta
cualquier cadena:

```php
'categoria' => ['nullable', 'string', 'max:60'],
```

Funciona porque solo hay un cliente y lo manda bien. Las consecuencias de que
siga así llegan más tarde, todas a la vez:

1. **Una versión vieja de la app** que mande `salarios` en vez de `salario`
   crea una categoría fantasma. Nadie se entera: no hay error, hay una fila más
   en la dona.
2. **Los presupuestos por categoría** comparan cadenas. Si no coinciden
   exactamente, el presupuesto deja de contar gastos sin avisar.
3. **No se pueden traducir.** El nombre visible está en el cliente, así que el
   cambio de idioma del perfil no las alcanza.
4. **Los informes del Módulo 12** agruparán por un campo que nadie valida.

Es exactamente el mismo razonamiento que llevó a `TipoCuenta`: un enum en el
servidor con etiqueta, icono y color, y el cliente pidiendo el catálogo en vez
de llevarlo dentro.

### Decisión

Se crea el enum `CategoriaMovimiento`, con las categorías de ingreso del
documento y las de gasto que ya usa la app, y un endpoint que las sirve.

Las de ingreso, según el documento: **salario, trabajo independiente, venta,
regalo, reembolso, intereses y otros**. Las que hoy tiene la app no son las
mismas —usa `freelance`, `negocio` e `inversiones`—, así que hay una
equivalencia que resolver en la migración (§6).

## 4. Modelo de datos

### Cambios en `movimientos`

| Columna | Tipo | Notas |
|---|---|---|
| `categoria` | `varchar(40)` | pasa a validarse contra el enum |
| `estado` | `varchar(12)` | `confirmado` por defecto, o `anulado` |
| `etiquetas` | `json` nullable | lista de cadenas libres |

`estado` e `etiquetas` son nullable o tienen valor por defecto: la migración no
toca ninguna fila existente.

### Estado: qué significa anular

Un ingreso anulado **se conserva y deja de contar**. No suma al saldo de su
cuenta, no aparece en los totales ni en la dona, pero sigue en el historial
marcado como anulado.

Es distinto de borrar, y la diferencia importa: un ingreso que se registró por
error y se borra desaparece sin rastro, y luego nadie entiende por qué el saldo
de marzo no cuadra con lo que recordaba. Anulado, la explicación está a la
vista.

> Esto obliga a revisar **todos** los sitios donde hoy se suman movimientos.
> Ver §8, riesgo 1: es el riesgo real del módulo.

### Etiquetas

Texto libre, varias por movimiento, sin catálogo. Son la vía de escape cuando
la categoría se queda corta: "reembolso" es la categoría, `viaje-medellín` la
etiqueta.

No se validan contra una lista porque su valor es precisamente que el usuario
invente las suyas. Sí se normalizan —minúsculas, sin espacios al borde— para
que `Viaje` y `viaje ` no sean dos.

## 5. API

| Método | Ruta | Qué hace |
|---|---|---|
| `GET` | `/api/categorias?tipo=ingreso` | catálogo con etiqueta, icono y color |
| `GET` | `/api/movimientos` | ya existe; se le añade filtro por `estado` y `etiqueta` |
| `POST` | `/api/movimientos` | valida `categoria` contra el enum |
| `PUT` | `/api/movimientos/{id}` | ya existe; se expone en la app |
| `POST` | `/api/movimientos/{id}/anular` | marca como anulado |
| `POST` | `/api/movimientos/{id}/restaurar` | deshace la anulación |
| `DELETE` | `/api/movimientos/{id}` | borrado real, se mantiene |

El catálogo se sirve desde el servidor para que el nombre visible se traduzca
con el idioma de la persona, que es el motivo por el que el enum lleva las
etiquetas y no solo los códigos.

`GET /api/movimientos` **excluye los anulados por defecto**, y los incluye con
`?incluir_anulados=1`. El valor por defecto es el que protege: cualquier
consulta que alguien escriba sin pensar en los anulados da el número correcto.

## 6. Migración de las categorías existentes

Las categorías de ingreso que usa la app no son las del documento. Antes de
validar contra el enum hay que decidir qué pasa con lo ya guardado:

| Lo que hay hoy | Pasa a ser |
|---|---|
| `salario` | `salario` |
| `freelance` | `trabajo_independiente` |
| `negocio` | `venta` |
| `inversiones` | `intereses` |
| `regalo` | `regalo` |
| `otros` | `otros` |
| cualquier otra cosa | `otros` |

`freelance` → `trabajo_independiente` es el nombre del documento para lo mismo.
`negocio` → `venta` e `inversiones` → `intereses` son aproximaciones: no son
sinónimos exactos, pero son la categoría del documento más cercana, y dejar una
categoría fuera del enum anularía el propósito de tener enum.

Pasos, en orden:

1. Anotar el recuento por categoría y tipo **antes** de migrar.
2. Reasignar según la tabla.
3. Reducir la columna a `varchar(40)` y empezar a validar.
4. Comprobar que el número total de movimientos y la suma por tipo no
   cambiaron.

Solo el paso 2 toca datos. Es reversible mientras exista la foto del paso 1.

## 7. Interfaz

### Registrar y editar

El formulario que ya existe en la hoja de acciones sirve para los dos casos:
abrirlo con un movimiento cargado lo convierte en edición. Un formulario aparte
para editar sería el mismo formulario dos veces, y a la tercera uno de los dos
se queda sin un campo.

Se le añade el selector de etiquetas.

### Tocar un movimiento de la lista

Hoy la lista de últimos movimientos solo permite borrar. Pasa a abrir el
movimiento: editar, anular o eliminar.

### Cómo se ve un anulado

Tachado y en gris, con la etiqueta "Anulado" y la opción de restaurar. Nunca
oculto: si desapareciera, anular y borrar se verían igual, y entonces no haría
falta anular.

### Categorías

El catálogo deja de estar en `home.page.ts` y se pide al servidor. Se cachea en
el dispositivo, porque no cambia y no merece una petición por pantalla.

## 8. Riesgos

1. **Que los anulados sigan contando en algún sitio.** Es el riesgo grande.
   Los movimientos se suman hoy en `ResumenMovimientos`, en `SaldosCuentas`, en
   `Cuenta::saldoActual()` y en `EvaluadorPresupuestos`. Si uno solo se olvida
   del filtro, el saldo deja de cuadrar con el historial y se rompe la promesa
   central del [Módulo 3](../cuentas/spec.md#41-el-saldo-se-calcula-no-se-guarda).
   Se cubre con el script de verificación del criterio 5 de cuentas, que
   recalcula todos los saldos movimiento a movimiento.
2. **Repetir la regresión del Bloque 3 de cuentas.** Validar `categoria` contra
   el enum rompe cualquier cliente que mande una categoría que no esté. El
   cambio en la app va en el mismo bloque que el cambio en la API, no en el
   siguiente. Está escrito en [`cuentas/task.md`](../cuentas/task.md) como
   lección, y este es el primer módulo donde vuelve a aplicar.
3. **Las equivalencias de la migración no son exactas.** `negocio` → `venta`
   cambia el nombre de algo que el usuario eligió. Si hay pocos movimientos
   afectados, mejor preguntarle; el recuento del paso 1 lo dirá.
4. **Que el módulo crezca hacia los gastos.** Ingresos y gastos comparten tabla,
   así que todo lo de aquí sirve para el Módulo 5. La tentación es hacer los dos
   a la vez. Se hace ingresos, y el Módulo 5 comprueba que hereda.

## 9. Criterios de aceptación

1. Un ingreso se registra eligiendo categoría del catálogo del **servidor**.
2. Mandar una categoría que no está en el enum devuelve 422 en español, no la
   guarda.
3. Un ingreso se puede editar desde la app: importe, fecha, categoría, cuenta,
   descripción y etiquetas.
4. Cambiar la cuenta de un ingreso ajusta el saldo de las dos cuentas.
5. Un ingreso anulado desaparece de los totales, la dona y los indicadores.
6. Un ingreso anulado **no** desaparece del historial: se ve tachado.
7. El saldo de una cuenta con ingresos anulados sigue cuadrando al recalcularlo
   movimiento a movimiento.
8. Restaurar un anulado devuelve el saldo y los totales a donde estaban.
9. Las etiquetas se guardan normalizadas y se puede filtrar por ellas.
10. Tras la migración, el número de movimientos y la suma por tipo son idénticos
    a los de antes.
11. Ninguna categoría queda fuera del enum después de migrar.
12. El nombre de las categorías cambia de idioma con la preferencia del perfil.
13. Un ingreso de otra persona devuelve 404, también al anular.

## 10. Fuera de alcance

- **Comprobantes adjuntos.** El documento los pone explícitamente "en el
  futuro". Necesitan almacenamiento de archivos, que hoy no existe.
- **Ingresos recurrentes.** Módulo 9.
- **Transferencias.** Módulo 6. Siguen sin ser ni ingreso ni gasto (§10, regla
  4), y este módulo no las toca.
- **Gastos.** Módulo 5, aunque herede casi todo.

## 11. Decisiones por tomar

1. **¿`estado` con dos valores o tres?** El documento dice "eliminar o anular" y
   lista "estado" entre los datos, sin enumerar cuáles. Dos (`confirmado`,
   `anulado`) cubren lo que pide. Un tercero, `pendiente`, tendría sentido para
   un ingreso esperado que aún no llegó —una factura emitida—, pero eso se
   parece más a los recurrentes del Módulo 9. **Propuesta: dos**, y que el
   Módulo 9 añada el tercero si lo necesita.
2. **¿Se respetan las categorías que el usuario ya eligió?** Ver riesgo 3.
3. **¿El catálogo de gastos se migra a la vez?** Comparten enum y tabla. Hacerlo
   de golpe evita migrar dos veces; hacerlo aparte reduce lo que puede romperse
   en una sola tanda. **Propuesta: el enum se crea completo con los dos tipos, y
   la validación se activa solo para ingresos**, para que el Módulo 5 la
   encienda cuando le toque.
