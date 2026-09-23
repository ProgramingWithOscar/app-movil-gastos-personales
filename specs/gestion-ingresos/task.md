# Tareas — Gestión de ingresos

Plan de implementación de [`spec.md`](./spec.md).

Leyenda: `[ ]` pendiente · `[~]` en curso · `[x]` hecho

> El Bloque 2 toca datos existentes y el Bloque 3 cambia el significado de
> "sumar movimientos" en toda la app. Son los dos puntos donde este módulo
> puede romper algo que hoy funciona.

---

## Bloque 0 · Lo que ya estaba

No hay nada que hacer aquí; queda anotado para que no se rehaga por error.
Todo esto salió de la [Fase B del dashboard](../dashboard/task.md).

- [x] Tabla `movimientos` con `tipo`, importe siempre positivo.
- [x] `POST`, `GET`, `PUT` y `DELETE /api/movimientos`.
- [x] Ingreso atado a una cuenta activa, sumando a su saldo.
- [x] Dashboard con ingresos: total, dona, categorías, comparación y tasa de
      ahorro.
- [x] Hoja de acciones que registra ingresos.

## Bloque 1 · Las categorías pasan al servidor

- [x] **1.1** Enum `CategoriaMovimiento` con las siete de ingreso del documento
      —salario, trabajo independiente, venta, regalo, reembolso, intereses,
      otros— y las ocho de gasto que ya usa la app, cada una con etiqueta,
      icono, color y a qué tipo pertenece.
- [x] **1.2** Las etiquetas salen de `lang/es` y `lang/en`, no del enum: el
      nombre visible tiene que cambiar con el idioma del perfil (criterio 12).
- [x] **1.3** `GET /api/categorias?tipo=ingreso|gasto`, con el catálogo
      completo. Sin `tipo`, las dos listas.
- [x] **1.4** Comprobación por `curl`: el catálogo responde en español y, con un
      usuario en inglés, en inglés.

**Listo cuando:** el servidor puede decir cuáles son las categorías válidas. No
las valida todavía — eso es el Bloque 3. ✅

### `otros` pertenece a los dos tipos

Es la única categoría que aparece en las dos listas, y comparte valor porque es
el mismo concepto: partirla en `otros_ingreso` y `otros_gasto` habría obligado a
reescribir los movimientos que ya usan `otros`, que son los más numerosos.

Por eso el enum expone `tipos()` —una lista— en vez de un `tipo()` suelto, y
`aplicaA()` para preguntar por uno concreto. El catálogo lo devuelve en la clave
`tipos`, para que el cliente sepa que esa categoría vale en los dos sitios.

### Verificado por `curl`

```
ingresos (7):  salario · trabajo_independiente · venta · regalo · reembolso ·
               intereses · otros
gastos (8):    alimentacion · transporte · vivienda · servicios · salud ·
               entretenimiento · compras · otros
sin filtro:    14 categorías   ← 7 + 8 menos `otros`, que no se repite

perfil en español → Salario · Trabajo independiente · Venta · Regalo ·
                    Reembolso · Intereses · Otros
perfil en inglés  → Salary · Freelance work · Sale · Gift · Refund ·
                    Interest · Other

?tipo=transferencia → 422 "Esa opción no es válida para el tipo."
sin sesión          → 401
```

El cambio de idioma (criterio 12) queda demostrado aquí, aunque el criterio no
se dará por cerrado hasta que la app pida el catálogo en el Bloque 3.

### Nombres que no son los del documento

Dos etiquetas en español se apartan a propósito:

- **`entretenimiento` → "Ocio"**, que es lo que ya enseñaba la app y cabe en las
  fichas sin partirse en dos líneas.
- Las claves se quedan **en español también en inglés**, porque son lo que se
  guarda en la base de datos. Traducirlas habría convertido cada cambio de
  idioma en una migración.

## Bloque 2 · Migrar lo que ya está guardado ⚠️

Único bloque que modifica datos existentes.

- [x] **2.1** Anotar el recuento por `tipo` y `categoria`, y la suma de importes
      por tipo, **antes** de tocar nada. Sin esa foto no se puede comprobar el
      criterio 10.
- [x] **2.2** Mirar cuántos movimientos caen en las equivalencias dudosas
      (`negocio` → `venta`, `inversiones` → `intereses`). Si son pocos, la
      decisión 2 de la spec §11 se resuelve preguntando; si son miles, se
      aplica la tabla y se anota.
- [x] **2.3** Migración que reasigna según la tabla de la spec §6.
- [x] **2.4** Reducir `categoria` a `varchar(40)`.
- [x] **2.5** Comprobar que el total de movimientos y la suma por tipo son
      idénticos a los de 2.1, y que no queda ninguna categoría fuera del enum
      (criterios 10 y 11).

**Listo cuando:** la foto de antes y la de después solo se diferencian en los
nombres de categoría que la tabla dice que cambian. ✅

### La decisión 2 se resolvió sola

No había que preguntar nada: **cero movimientos con `negocio` o `inversiones`**.
Solo existían dos ingresos en toda la base, uno `salario` y otro `freelance`.
Las dos equivalencias discutibles de la spec §6 no llegaron a usarse; la única
que se aplicó —`freelance` → `trabajo_independiente`— es el mismo concepto con
el nombre del documento.

Las equivalencias se quedan en la migración de todos modos, porque también tiene
que correr en producción, donde los datos son otros.

### Antes y después

```
tipo     categoria                  n        total
gasto    alimentacion            1289  4,218,554.22
gasto    entretenimiento         1285  3,178,090.29
gasto    vivienda                1277  4,318,615.98
gasto    transporte              1266  3,364,766.90
gasto    compras                 1248  3,177,439.46
gasto    salud                   1236  3,102,901.88
gasto    otros                   1232  3,125,526.54
gasto    servicios               1177  3,049,992.17
ingreso  salario                    1  3,200,000.00
ingreso  freelance  → trabajo_independiente
                                    1    850,000.00

totales: gasto 10010 / 27,535,887.44 · ingreso 2 / 4,050,000.00
```

`diff` de las dos fotos: **una sola línea**, la del renombrado. Ningún recuento
ni total cambió (criterio 10), y no queda ninguna categoría fuera del enum
(criterio 11).

### El valor por defecto de la columna era inválido

`categoria` tenía `DEFAULT 'general'`, y `general` no está en el catálogo. No
había ninguna fila así, pero era una puerta abierta: cualquier inserción que no
pusiera categoría habría creado un valor que el Bloque 3 rechaza. Ahora el
defecto es `otros`.

### Lo que generaba datos imposibles

`MovimientoFactory` inventaba ingresos con `freelance`, `negocio` e
`inversiones` — categorías que a partir del Bloque 3 el servidor rechazará. Una
factoría que produce datos que no podrían existir en producción esconde justo
los fallos que se buscan, así que ahora sale del enum. Lo mismo en
`CargaDemoSeeder`, que tenía su propia copia de la lista.

### Comprobado que no se rompió nada

```
ingreso con trabajo_independiente → 201
gasto con alimentacion            → 201
categoría de 50 caracteres        → 422   ← no un error de base de datos
dashboard                         → responde con sus categorías
```

Lo de los 50 caracteres importa: al estrechar la columna a 40, la validación
seguía en `max:60`, así que una cadena de entre 41 y 60 habría pasado el
validador y reventado contra la base. Ajustada a 40 hasta que el Bloque 3 la
sustituya por el enum.

## Bloque 3 · Validar, y a la vez arreglar el cliente

> **La decisión 3 de la spec §11 cambia a la luz del Bloque 2.** La propuesta
> era validar solo los ingresos y dejar los gastos para el Módulo 5. Pero la
> foto de 2.1 enseñó que **las ocho categorías de gasto ya coinciden con el
> enum**: no hay ni una fuera. Validar los dos tipos no cuesta nada más y cierra
> el agujero entero en vez de la mitad. La lista del frontend también coincide,
> salvo los tres nombres de ingreso que ya se migraron.

- [x] **3.1** `MovimientoRequest` —hoy la validación está suelta dentro del
      controlador— con `categoria` validada contra el enum, filtrando por el
      `tipo` del movimiento: una categoría de gasto en un ingreso es un 422.
- [x] **3.2** Mensajes en español para los casos nuevos, como ya se hizo con
      `cuenta_id`.
- [x] **3.3** **En este mismo bloque**, el frontend deja de llevar el catálogo
      dentro y lo pide a `GET /api/categorias`, con caché en el dispositivo.

> 3.3 no se pospone. Al volver `cuenta_id` obligatorio en el Bloque 3 de
> cuentas, la app se quedó dos semanas sin poder registrar nada porque el
> cambio en el cliente se dejó para el bloque siguiente. Esta es la misma
> forma de romperlo.

- [x] **3.4** Comprobar que registrar un ingreso sigue funcionando de punta a
      punta desde la app **antes** de dar el bloque por cerrado.

**Listo cuando:** se cumplen los criterios 1 y 2, y la app sigue registrando
ingresos. ✅

### Se validan los dos tipos, no solo los ingresos

Como anticipaba la nota de arriba: los gastos ya estaban limpios, así que
validarlos no costaba nada. **La decisión 3 de la spec §11 queda cerrada** y el
Módulo 5 no hereda deuda por este lado.

### Verificado por `curl`

```
ingreso + salario                          201
gasto   + vivienda                         201
ingreso + otros                            201   ← vale en los dos tipos
gasto   + otros                            201

ingreso + vivienda  (categoría de gasto)   422
gasto   + salario   (categoría de ingreso) 422
gasto   + freelance (nombre viejo)         422
ingreso + inventada                        422

categoria: null       201, guardado como `otros`
sin la clave          201, guardado como `otros`
```

Los dos últimos casos eran un 500 esperando a pasar: la columna no admite nulos,
así que `categoria: null` reventaba contra la base en vez de dar un 422.
`prepareForValidation()` lo convierte en `otros`, que además es lo que significa
no clasificar un movimiento.

### El mensaje de error también cambia de idioma

Los mensajes estaban escritos a mano dentro del FormRequest, así que el catálogo
se traducía y el error no:

```
catálogo en inglés → Salary · Freelance work · Sale · …
error              → "Esa categoría no existe o no es de este tipo…"
```

Se movieron a `lang/es/movimientos.php` y `lang/en/movimientos.php`. De paso los
dos de `cuenta_id`, que tenían el mismo problema desde el Módulo 3:

```
es → Elige la cuenta de la que sale o entra el dinero.
en → Choose the account the money comes from or goes to.
```

### De punta a punta desde la app

Es la comprobación que habría cazado la regresión del Módulo 3, así que se hizo
de verdad, no leyendo el código: login, abrir la hoja, elegir Ingreso, tocar una
categoría y guardar.

- El formulario enseña **las siete categorías del servidor** —Salario, Trabajo
  independiente, Venta, Regalo, Reembolso, Intereses, Otros— con sus iconos y
  colores. Las tres viejas (Freelance, Negocio, Inversiones) ya no están.
- Guardado un ingreso de $250.000 con `trabajo_independiente`, una categoría que
  antes de este módulo no existía.
- El dashboard pasó de $4.050.000 a $4.300.000 de ingresos y el dinero
  disponible de $4.239.000 a $4.489.000.

### El catálogo se cachea en el dispositivo

`CategoriasService` guarda una copia en `Preferences` y la refresca en segundo
plano. Sin ella, la pantalla principal tendría que esperar a la red para saber
pintar el nombre y el color de cada categoría del historial.

Si no hay red **ni** copia, el catálogo queda vacío y el formulario se queda sin
categorías. Es visible y molesto, pero inventar una lista en el cliente sería
volver al problema que este bloque resuelve: ofrecer categorías que el servidor
va a rechazar.

## Bloque 4 · Anular ⚠️

- [ ] **4.1** Columna `estado` con `confirmado` por defecto, y enum
      `EstadoMovimiento`.
- [ ] **4.2** `POST /api/movimientos/{id}/anular` y `/restaurar`.
- [ ] **4.3** Scope `confirmados()` en `Movimiento`, y `GET /api/movimientos`
      excluyendo anulados salvo `?incluir_anulados=1`.
- [ ] **4.4** Recorrer **los cuatro sitios** donde hoy se suman movimientos y
      excluir los anulados:
      - [ ] `Cuenta::saldoActual()`
      - [ ] `SaldosCuentas`
      - [ ] `ResumenMovimientos`
      - [ ] `EvaluadorPresupuestos`
- [ ] **4.5** Volver a pasar el script del criterio 5 de cuentas —el que
      recalcula cada saldo movimiento a movimiento— con ingresos anulados de por
      medio. Es la prueba de que no se olvidó ninguno (criterio 7).
- [ ] **4.6** Comprobar que restaurar deja saldo y totales como estaban
      (criterio 8).

**Listo cuando:** se cumplen los criterios 5, 7 y 8. El 4.5 es el que vale: los
otros cuatro puntos se pueden dar por buenos leyendo el código y estar mal.

## Bloque 5 · Etiquetas

- [ ] **5.1** Columna `etiquetas` json nullable, con cast a array.
- [ ] **5.2** Normalizar al guardar: minúsculas, sin espacios al borde, sin
      repetidas, máximo razonable por movimiento.
- [ ] **5.3** Filtro `?etiqueta=` en `GET /api/movimientos`.
- [ ] **5.4** Selector de etiquetas en el formulario, sugiriendo las que la
      persona ya usó.

**Listo cuando:** se cumple el criterio 9.

## Bloque 6 · Pantallas

- [ ] **6.1** `MovimientosService.actualizar()`, que hoy no existe aunque la API
      sí lo soporte.
- [ ] **6.2** El formulario de la hoja de acciones admite abrirse con un
      movimiento cargado y pasa a ser el de editar. No se hace uno nuevo.
- [ ] **6.3** Tocar un movimiento de la lista abre sus opciones: editar, anular,
      eliminar.
- [ ] **6.4** Los anulados se ven tachados, en gris, con "Anulado" y la opción
      de restaurar (criterio 6).
- [ ] **6.5** Cambiar la cuenta de un ingreso al editarlo, comprobando que las
      dos cuentas ajustan su saldo (criterio 4).

**Listo cuando:** un ingreso mal capturado se puede arreglar sin borrarlo y
volver a crearlo.

## Bloque 7 · Cierre

- [ ] **7.1** Repasar los 13 criterios de la spec §9, con evidencia anotada.
- [ ] **7.2** Comprobar que un ingreso ajeno devuelve 404 también al anular y al
      restaurar (criterio 13). Los endpoints nuevos son los que se olvidan.
- [ ] **7.3** Documentar en el README los endpoints nuevos y el cambio de
      comportamiento de `GET /api/movimientos`.
- [ ] **7.4** Resolver las tres decisiones de la spec §11 y anotarlas.
- [ ] **7.5** Anotar qué hereda el Módulo 5 (gastos) y qué le queda por hacer.

**Listo cuando:** los 13 criterios están verificados y anotados.

---

## Después de este módulo

**Módulo 5 (gastos)** se queda casi hecho: comparte tabla, enum, estados,
etiquetas y pantallas. Lo que le quedará es encender la validación de categoría
para los gastos (decisión 3 de la spec §11) y lo que el documento pida de más.

## Riesgos

Los cuatro están en la [spec §8](./spec.md#8-riesgos). El orden de los bloques
responde a ellos: primero el catálogo sin validar (Bloque 1), luego migrar los
datos (2), luego validar junto con el cliente (3), y solo entonces tocar cómo se
suman los movimientos (4), que es lo que puede descuadrar los saldos.
