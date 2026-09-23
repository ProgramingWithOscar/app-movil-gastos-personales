# Tareas — Cuentas financieras

Plan de implementación de [`spec.md`](./spec.md).

Leyenda: `[ ]` pendiente · `[~]` en curso · `[x]` hecho

> El Bloque 3 toca datos existentes y es el único punto irreversible del módulo.
> No empezarlo sin tener hechos el 1 y el 2.

---

## Bloque 1 · Modelo

- [x] **1.1** Enum `TipoCuenta` con los nueve tipos del documento, cada uno con
      su etiqueta, icono y color por defecto. Así crear una cuenta son dos
      campos y no seis.
- [x] **1.2** Migración `create_cuentas_table` según la spec §3, con único
      `(user_id, nombre)` e índice `(user_id, archivada)`.
- [x] **1.3** Modelo `Cuenta` con `BelongsToUser`, casts y relación
      `movimientos()`.
- [x] **1.4** `saldoActual()`: `saldo_inicial + ingresos − gastos`, calculado con
      una agregación, nunca leído de una columna.
- [x] **1.5** Scopes `activas()` y `archivadas()`.
- [x] **1.6** `CuentaFactory` con estados `archivada()` y `tarjetaCredito()`.

- [x] **1.7** *(adelantado del Bloque 3)* Columna `cuenta_id` **nullable** en
      `movimientos`, con FK `restrictOnDelete` e índice `(cuenta_id, fecha)`.
      Sin ella `saldoActual()` no se puede ejercitar. Añadir una columna
      nullable no toca ningún dato existente: lo irreversible del Bloque 3 es el
      relleno y volverla obligatoria, que siguen pendientes.

**Listo cuando:** `Cuenta::first()->saldoActual()` devuelve el número correcto
para una cuenta con ingresos y gastos. ✅

Verificado con script:

```
saldo sin movimientos: 500000   (esperado 500000)
saldo +300k -200k:     600000   (esperado 600000)
tras borrar un gasto:  720000   (esperado 720000)
tarjeta credito:       saldo=-450000  esDinero=false
activas ordenadas:     Nequi, Prueba saldo, Visa   (favorita primero)
nombre duplicado:      rechazado
borrar con movimientos: bloqueado por la FK
```

## Bloque 2 · API de cuentas

- [x] **2.1** `CuentaController` con index, store, show, update y destroy.
- [x] **2.2** `destroy` **archiva**, no borra. Borrado real solo si la cuenta no
      tiene ningún movimiento.
- [x] **2.3** `CuentaResource` con el saldo calculado y el número de movimientos.
- [x] **2.4** El `index` devuelve además `resumen` con `saldo_total`, `deuda` y
      `patrimonio_neto`, con las tarjetas de crédito separadas (spec §4.3).
- [x] **2.5** `PUT /cuentas/{id}/favorita`, desmarcando la anterior.
- [x] **2.6** `GET /cuentas/{id}/movimientos` paginado.
- [x] **2.7** Validación: nombre único por usuario, tipo válido, moneda
      restringida a la principal (spec §7).
- [x] **2.8** Comprobación por `curl`: crear, listar con saldo, archivar, y que
      una cuenta ajena devuelva 404.

**Listo cuando:** se cumplen los criterios 1, 2, 9 y 11 de la spec §9. ✅

Verificado por `curl`:

```
crear cuentas          201 201 201
nombre duplicado       422        ← criterio 11
moneda no principal    422        ← spec §7
cuenta ajena (GET)     404        ← criterio 9
cuenta ajena (DELETE)  404

resumen: saldo_total 2.650.000 | deuda 800.000 | patrimonio 1.850.000
         (la tarjeta de crédito no resta del total: es deuda aparte)

destroy con movimientos → "Cuenta archivada. Conserva su historial..."
destroy sin movimientos → "Cuenta eliminada."
archivada: 8 movimientos conservados y fuera del saldo total  ← criterios 6 y 7
```

Los saldos se calculan con **una sola consulta agregada para todas las cuentas**
(`SaldosCuentas`), no una por cuenta: con diez cuentas serían once viajes a la
base de datos solo para pintar una lista.

## Bloque 3 · Atar los movimientos a una cuenta ⚠️

Único bloque que modifica datos existentes.

- [x] **3.1** Anotar los totales actuales por usuario **antes** de migrar. Sin
      esa foto no hay forma de comprobar el criterio 13.
- [x] **3.2** Migración en cuatro pasos (spec §5): ~~columna nullable~~ (hecha
      en 1.7) → cuenta `Efectivo` por usuario con movimientos → asignar →
      hacerla obligatoria.
- [x] **3.3** FK `restrictOnDelete` e índice `(cuenta_id, fecha)`. *(hecho en 1.7)*
- [x] **3.4** `MovimientoController` exige `cuenta_id` y valida que la cuenta sea
      del usuario y no esté archivada.
- [x] **3.5** `MovimientoResource` incluye la cuenta (id, nombre, color, icono).
- [x] **3.6** Verificar que los totales por usuario son idénticos a los de 3.1.
- [x] **3.7** Comprobar que un movimiento sin `cuenta_id` devuelve 422 con
      mensaje claro, no un error genérico de base de datos.

**Listo cuando:** se cumplen los criterios 12 y 13 de la spec §9, con la
comparación de totales anotada. ✅

### Totales antes y después de la migración

```
user_id | tipo    |     n |          total
1       | gasto   |     3 |     198,000.00
2       | gasto   |     6 |   2,286,000.00
2       | ingreso |     2 |   4,050,000.00
4       | gasto   | 10000 |  25,026,887.44

movimientos totales: 10011
sin cuenta_id:  antes 10003  →  después 0
```

`diff` de ambas fotos: **sin diferencias**. Ningún total histórico cambió
(criterio 13).

La migración creó una cuenta `Efectivo` con saldo inicial 0 para los usuarios 1
y 4, que eran los que tenían movimientos huérfanos. El usuario 2 ya tenía
cuenta, así que reutilizó la suya.

### Validación de `cuenta_id`

```
sin cuenta_id:        "Elige la cuenta de la que sale o entra el dinero."
cuenta ajena:         "Esa cuenta no existe, no es tuya o está archivada."
cuenta archivada:     "Esa cuenta no existe, no es tuya o está archivada."
cuenta válida:        201
```

Los tres casos son 422 con mensaje en español, no un error de base de datos
(criterio 12).

## Bloque 4 · Saldo en el dashboard

Cierra lo que quedó pendiente de la [Fase B del dashboard](../dashboard/task.md).

- [x] **4.1** `GET /api/dashboard` añade `saldo` con total, deuda y patrimonio.
- [x] **4.2** Tarjeta de saldo total en la pantalla principal.
- [x] **4.3** Mostrar la deuda solo si hay tarjetas de crédito: una fila que
      siempre dice cero es ruido.
- [x] **4.4** Marcar en `specs/dashboard/task.md` los puntos de Fase B que este
      bloque desbloquea.

**Listo cuando:** el dashboard responde "cuánto tengo", no solo "cuánto gasté". ✅

Verificado en el emulador:

```
DINERO DISPONIBLE
$4,264,000
Debes $800,000 · Neto $3,464,000
```

La fila de deuda solo aparece porque hay una tarjeta de crédito. Sin tarjetas se
oculta: una línea que siempre dice cero compite con el número que sí importa.

Queda fuera **la evolución del saldo**: necesita una serie temporal, que es
territorio del Módulo 12 (reportes).

## Bloque 5 · Pantallas

- [x] **5.1** `CuentasService` y modelos TypeScript.
- [x] **5.2** Pantalla de cuentas: cabecera con saldo, lista ordenada con la
      favorita primero, archivadas plegadas al final.
- [x] **5.3** Formulario de crear y editar, con icono y color por defecto según
      el tipo.
- [x] **5.4** Detalle de cuenta con su historial.
- [x] **5.5** Archivar con confirmación que explique qué implica: deja de sumar,
      pero conserva el historial.
- [x] **5.6** Selector de cuenta en el formulario de movimiento, con la favorita
      preseleccionada.
- [x] **5.7** La lista de movimientos muestra a qué cuenta pertenece cada uno.
- [x] **5.8** Estado vacío: sin cuentas, explicar para qué sirven antes de pedir
      crear una.

**Listo cuando:** se puede llevar el dinero de dos cuentas distintas de punta a
punta desde la app. ✅

### Regresión que arregló este bloque

Al volver `cuenta_id` obligatorio en el Bloque 3, la app se quedó sin poder
registrar nada: seguía enviando el movimiento sin cuenta y recibía un 422. El
backend estaba bien y el frontend también, pero el contrato entre ambos quedó
roto durante dos semanas.

**Para la próxima:** un campo que pasa a ser obligatorio en la API se acompaña
del cambio en el cliente en el mismo bloque, no en el siguiente.

### Dónde vive la pantalla

Como propone la spec §11.1: se entra desde la tarjeta de saldo del dashboard
—que ahora es un botón— y desde Perfil. La barra de pestañas no se tocó: sus
cinco huecos siguen ocupados.

### Verificado en el emulador

- Dashboard con "Dinero disponible $4.239.000 · Debes $800.000 · Neto $3.439.000".
- Pantalla de cuentas con Bancolombia (favorita, 9 movimientos) y Visa Oro en
  rojo por ser deuda.
- Formulario de movimiento con el selector de cuenta y la favorita ya elegida.

## Bloque 6 · Cierre

- [x] **6.1** Repasar los 13 criterios de la spec §9, con evidencia.
- [x] **6.2** Verificar el criterio 5 con un script: recalcular todos los saldos
      desde cero y compararlos con los que devuelve la API.
- [x] **6.3** Documentar los endpoints en el README.
- [x] **6.4** Resolver las tres decisiones abiertas de la spec §11.
- [x] **6.5** Medido: **25,4 ms** de media con 10 000 movimientos, con el índice
      `(cuenta_id, fecha)` y `type=ref`. Doce veces por debajo del objetivo de
      300 ms, así que **no se cachea el saldo**.

**Listo cuando:** los 13 criterios están verificados y anotados. ✅

El script del 6.2 es el que más vale: recorre cada movimiento uno a uno, sin
agregaciones, y compara con `saldoActual()`. Las 5 cuentas cuadran, incluida la
de 10 000 movimientos. Es la prueba de la promesa central del módulo.

---

## Después de este módulo

Queda desbloqueado el **Módulo 6 (transferencias)**, que es el que da sentido
completo a tener varias cuentas. Necesitará:

- El tipo `transferencia` en `TipoMovimiento`.
- Cuenta origen y destino en el mismo registro, o dos registros enlazados.
- Que las transferencias **no** cuenten como gasto ni como ingreso en las
  estadísticas (§10, regla 4 del documento). Es la trampa del módulo: si se
  cuentan, todos los totales del dashboard se inflan.

## Riesgos

1. **El saldo que no cuadra.** Se calcula siempre desde los movimientos; el
   criterio 5 es la prueba y el 6.2 la verifica de forma automatizable.
2. **La migración del Bloque 3.** Irreversible en la práctica. Probar contra una
   copia y comparar totales antes y después.
3. **Volver `cuenta_id` obligatorio.** Rompe cualquier cliente que no envíe
   cuenta. La app se despliega a la vez, pero el mensaje de error debe explicar
   qué falta.
