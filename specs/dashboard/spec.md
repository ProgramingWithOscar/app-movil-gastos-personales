# Spec — Dashboard principal

**Módulo:** 2 · Pantalla principal después del inicio de sesión
**Origen:** [`sistema_gastos_personales.md`](../../sistema_gastos_personales.md) §3 Módulo 2, §12 (flujo), §16 (métricas y fórmulas), §17 (móvil y PC)
**Estado:** Fase A implementada y verificada · Fases B y C bloqueadas por sus módulos
**Depende de:** [Usuarios y roles](../usuarios/spec.md) (implementado)

---

## 1. Objetivo

Responder de un vistazo la pregunta que el documento define como la razón de ser
del producto (§20):

> ¿Cuánto dinero tengo, cuánto he gastado, en qué lo he gastado y cuánto puedo
> gastar sin superar mi presupuesto?

El dashboard no crea información: la resume. Toda su lógica es de lectura y
agregación, y por eso su principal riesgo no es la corrección funcional sino el
**rendimiento** y la **coherencia entre números** que la persona compara a
simple vista.

## 2. El problema de las dependencias

El módulo 2 del documento describe un dashboard que consume datos de casi todos
los demás módulos. Hoy solo existen gastos. Enumerar esto por adelantado evita
prometer una pantalla que no se puede construir todavía:

| Dato del documento | Depende de | ¿Se puede hoy? |
|---|---|---|
| Gastos del período | Módulo 5 (gastos) | **Sí** |
| Gasto por categoría | Módulo 5 + 7 | **Sí** |
| Últimos movimientos | Módulo 5 | **Sí** |
| Comparación con período anterior | Módulo 5 | **Sí** |
| Promedio diario de gasto | Módulo 5 | **Sí** |
| Categoría con mayor gasto | Módulo 5 | **Sí** |
| Saldo total | Módulo 3 (cuentas) | No |
| Dinero disponible | Módulo 3 | No |
| Evolución del saldo | Módulo 3 | No |
| Ingresos del período | Módulo 4 (ingresos) | No |
| Balance del período | Módulos 4 y 5 | No |
| Presupuesto utilizado | Módulo 8 (presupuestos) | No |
| Próximos pagos | Módulo 10 (recurrentes) | No |
| Metas de ahorro | Módulo 9 (metas) | No |

Por eso el módulo se entrega en **tres fases**, y cada fase deja una pantalla
completa y coherente, no una llena de huecos.

### Fase A — lo que se puede hoy (gastos)

Total gastado del período, distribución por categoría, últimos movimientos,
comparación con el período anterior, promedio diario y categoría dominante.

### Fase B — cuando existan cuentas e ingresos

Saldo total, ingresos, balance, tasa de ahorro y evolución del saldo.

### Fase C — cuando existan presupuestos, metas y recurrentes

Presupuesto consumido, próximos pagos y progreso de metas.

**Regla de diseño:** una tarjeta cuyo módulo no existe **no se muestra**. Nada
de valores en cero ni de "próximamente" en la pantalla principal: es la primera
que se ve al entrar y debe transmitir que el sistema funciona, no que está a
medias.

## 3. Estado actual

**La Fase A está completa.**

Backend:

- [`CalculadorPeriodos`](../../backend/app/Services/CalculadorPeriodos.php) y
  [`Periodo`](../../backend/app/Support/Periodo.php) — rangos en la zona horaria
  de la persona, período anterior de calendario y rangos personalizados.
- [`ResumenGastos`](../../backend/app/Services/ResumenGastos.php) — agregación,
  promedio, proyección y comparación.
- [`DashboardController`](../../backend/app/Http/Controllers/Api/DashboardController.php)
  en `GET /api/dashboard`.

App:

- Encabezado fijo con saludo, total del período y selector Gastos / Ingresos.
- Selector de período que se recuerda entre sesiones.
- Dona en SVG con lista de categorías y barras de proporción.
- Tres indicadores: variación, promedio diario y categoría dominante.
- Últimos movimientos con deslizar para eliminar.
- Esqueletos de carga, dos estados vacíos distintos y estado de error con
  reintentar.

## 4. Contrato de la API

### `GET /api/dashboard`

Sustituye a las dos llamadas actuales. Un solo viaje: en móvil, dos peticiones
en serie sobre una red lenta se notan.

Parámetros:

| Parámetro | Valores | Por defecto |
|---|---|---|
| `periodo` | `dia`, `semana`, `mes`, `anio`, `personalizado` | `mes` |
| `desde` / `hasta` | fecha ISO, solo si `periodo=personalizado` | — |

Respuesta:

```json
{
  "data": {
    "periodo": { "tipo": "mes", "desde": "2026-08-01", "hasta": "2026-08-31", "dias": 31, "dias_transcurridos": 23 },
    "gastos": {
      "total": 198000,
      "movimientos": 3,
      "promedio_diario": 8608.7,
      "proyeccion_fin_periodo": 266869.6,
      "por_categoria": [
        { "categoria": "servicios", "total": 95000, "cantidad": 1, "porcentaje": 48.0 }
      ],
      "categoria_principal": { "categoria": "servicios", "total": 95000, "porcentaje": 48.0 }
    },
    "comparacion": {
      "periodo_anterior": { "desde": "2026-07-01", "hasta": "2026-07-31", "total": 240000 },
      "diferencia": -42000,
      "variacion_porcentual": -17.5,
      "tendencia": "baja"
    },
    "movimientos_recientes": [ { "id": 3, "descripcion": "Internet", "monto": "95000.00", "categoria": "servicios", "fecha": "2026-08-20" } ]
  }
}
```

Las claves de las fases B y C se añaden a este mismo objeto cuando existan
(`saldo`, `ingresos`, `balance`, `presupuestos`, `metas`, `proximos_pagos`).
La app debe tolerar su ausencia, no asumirla.

## 5. Cálculos

Todos se hacen en el servidor. Que el móvil sume es tentador pero obliga a
descargar todos los movimientos, y con un año de historial eso deja de ser
viable (§5 del documento, rendimiento).

### Períodos

Se calculan en la **zona horaria del usuario**, no la del servidor. Una persona
en Bogotá que registra un gasto a las 23:00 debe verlo dentro de "hoy", no del
día siguiente.

| Período | Rango |
|---|---|
| `dia` | de 00:00 a 23:59 de hoy |
| `semana` | del lunes al domingo de la semana actual |
| `mes` | del día 1 al último del mes actual |
| `anio` | del 1 de enero al 31 de diciembre |

El período anterior es el rango inmediatamente previo del mismo tipo y longitud:
el mes pasado para `mes`, la semana pasada para `semana`.

### Promedio diario

```
promedio_diario = total_gastado / dias_transcurridos
```

`dias_transcurridos` es el número de días del período que ya ocurrieron, no la
longitud total. En un mes en curso, dividir entre 31 el día 5 da un promedio
falsamente bajo. Para períodos ya cerrados, ambos coinciden.

### Proyección

```
proyeccion = promedio_diario × dias_totales_del_periodo
```

Solo tiene sentido en un período en curso. En uno cerrado se devuelve igual al
total.

### Variación frente al período anterior

```
variacion = (total_actual - total_anterior) / total_anterior × 100
```

**Casos borde que hay que resolver, no ignorar:**

- `total_anterior = 0` y `total_actual > 0` → no hay porcentaje que calcular.
  Se devuelve `variacion_porcentual: null` y `tendencia: "sin_referencia"`. La
  app muestra "sin datos del período anterior", nunca "∞%" ni "100%".
- Ambos en cero → `variacion_porcentual: 0`, `tendencia: "igual"`.
- `tendencia` es `sube` / `baja` / `igual` / `sin_referencia`. Se calcula en el
  servidor para que la app no tenga que decidir el signo ni el color.

**Ojo con la semántica:** en gastos, "baja" es una buena noticia. El color verde
va con la reducción de gasto, al revés de lo que sería en ingresos. Cuando
llegue la Fase B habrá que separar ambos criterios.

### Porcentajes por categoría

Se redondean a un decimal **en el servidor**. Si la dona y la lista redondearan
por separado podrían mostrar números distintos para el mismo dato, y eso destruye
la confianza en la pantalla.

La suma de porcentajes redondeados puede dar 99.9 o 100.1. Es aceptable y no se
fuerza a 100: cuadrarlo a la fuerza falsearía alguna categoría.

## 6. Interfaz

Orden vertical, de lo más importante a lo más accesorio:

1. **Encabezado** — saludo con el nombre, total del período, selector Gastos /
   Ingresos.
2. **Selector de período** — Día / Semana / Mes / Año.
3. **Gráfico de dona** con el total en el centro.
4. **Indicadores** — tres tarjetas pequeñas: variación frente al período
   anterior, promedio diario y categoría dominante.
5. **Distribución por categoría** — lista con barra de proporción.
6. **Últimos movimientos** — cinco, con acceso a la lista completa.
7. **Acción rápida** — botón `+` central de la barra de pestañas.

### Estados

Los tres estados se diseñan explícitamente, no solo el feliz:

- **Cargando:** esqueletos con la forma del contenido real, no un spinner
  centrado. Evita el salto de layout cuando llegan los datos.
- **Sin datos:** ilustración, texto que explica qué hacer y botón para registrar
  el primer gasto. Solo aparece si el usuario no tiene ningún movimiento; si
  simplemente no gastó en ese período, el mensaje es distinto ("sin gastos en
  este período").
- **Error de red:** mensaje y botón de reintentar, conservando en pantalla los
  últimos datos cargados si los hay.

### Actualización

- `pull-to-refresh` en la pantalla.
- Recarga automática al volver a la pestaña tras registrar un movimiento.
- Al cambiar de período, solo se recarga el contenido, no el encabezado.

## 7. Rendimiento

Requisito del documento (§5): las consultas deben estar paginadas y los gráficos
cargar de forma eficiente.

- La agregación por categoría se hace con `GROUP BY` en la base de datos.
- Índice compuesto `(user_id, fecha)` en `gastos` — ya creado.
- Los movimientos recientes se limitan a 5 con `LIMIT`.
- Objetivo: la respuesta del dashboard por debajo de 300 ms con 10 000
  movimientos.
- Si se supera, la salida es cachear el resumen por usuario y período,
  invalidándolo al crear, editar o borrar un movimiento. **No se implementa
  todavía:** cachear antes de medir añade una fuente de datos obsoletos sin
  saber si hacía falta.

## 8. Criterios de aceptación

1. Al entrar, la pantalla muestra el saludo con el nombre de pila y el total del
   mes en curso.
2. Cambiar de período recalcula todo el contenido sin recargar la pantalla.
3. La suma de los totales por categoría es igual al total mostrado.
4. Un usuario no ve jamás datos de otro (heredado del Bloque 5 de usuarios).
5. Con el período anterior en cero, la comparación dice "sin referencia" y no
   muestra un porcentaje.
6. El promedio diario de un mes en curso divide entre los días transcurridos, no
   entre los días del mes.
7. Un usuario sin ningún movimiento ve el estado vacío con acceso directo a
   registrar el primero.
8. Sin conexión, la pantalla muestra un error con opción de reintentar y no una
   pantalla en blanco.
9. Los períodos respetan la zona horaria del usuario: un gasto de las 23:00 en
   Bogotá cuenta como de hoy.
10. La pantalla hace **una sola** petición al cargar.
11. Al registrar un gasto, el total y la dona se actualizan sin salir y volver.
12. Las tarjetas de módulos inexistentes no aparecen.

## 9. Verificación de los criterios

Repaso de §8, con la evidencia de cada uno:

| # | Criterio | Estado | Evidencia |
|---|---|---|---|
| 1 | Saludo con nombre y total del mes | ✅ | Captura del emulador |
| 2 | Cambiar de período recalcula sin recargar | ✅ | Captura tras pulsar "Día" |
| 3 | Suma de categorías = total | ✅ | Script de verificación: 198 000 = 198 000 |
| 4 | Nadie ve datos de otro | ✅ | Scope global del Bloque 5 de usuarios |
| 5 | Período anterior en cero → "sin referencia" | ✅ | Visible en la app; sin porcentaje |
| 6 | Promedio entre días transcurridos | ✅ | "En 23 día(s)", no 31 |
| 7 | Estado vacío con acceso a registrar | ✅ | Dos variantes implementadas |
| 8 | Error con reintentar, sin pantalla en blanco | ✅ | Probado apagando la red del emulador |
| 9 | Períodos en la zona horaria del usuario | ✅ | Test unitario: 04:30 UTC = día anterior en Bogotá |
| 10 | Una sola petición al cargar | ✅ | `HomePage` solo llama a `dashboardService.cargar()` |
| 11 | Registrar un gasto actualiza el total | ✅ | `cargar()` tras guardar, más `ionViewWillEnter` |
| 12 | Tarjetas de módulos inexistentes no aparecen | ✅ | No hay saldo, ingresos ni presupuestos en pantalla |

Los criterios 1, 2, 5, 6, 7 y 8 se comprobaron mirando la app en el emulador; el
3, 9 y 10 con scripts y tests; el 4, 11 y 12 por revisión del código.

## 10. Fuera de alcance

- Gráficos de evolución temporal (líneas o barras por mes): son del Módulo 12,
  Reportes.
- Filtros avanzados y búsqueda: Módulo 13.
- Personalizar qué tarjetas se ven o su orden.
- Exportación desde el dashboard.

## 11. Decisiones tomadas

1. **Un solo endpoint.** `GET /api/dashboard` devuelve todo. Un endpoint por
   tarjeta cachearía mejor, pero multiplicaría las peticiones en móvil y el
   rendimiento medido (§11) no da ningún motivo para dividirlo. Si algún día una
   tarjeta resulta cara —una proyección con histórico, por ejemplo— se saca esa
   sola a su propio endpoint.

2. **El selector "Ingresos" se queda visible** y avisa de que llega después.
   Ocultarlo sería más honesto sobre lo que hoy existe, pero esconde hacia dónde
   va el producto; y el aviso deja claro que no está roto. Se revisa cuando
   exista el Módulo 4.

3. **El período se recuerda entre sesiones**, guardado en el dispositivo con
   `@capacitor/preferences`. Cada persona tiende a mirar siempre el mismo rango,
   y volver a "mes" en cada arranque obliga a repetir el mismo gesto a diario.
   El valor guardado se valida contra la lista de períodos conocidos antes de
   usarse.

## 12. Rendimiento medido

Con 10 006 movimientos en la tabla (3 215 dentro del año consultado), cinco
corridas por período:

| Período | Tiempo medio |
|---|---|
| Día | 21,2 ms |
| Semana | 33,1 ms |
| Mes | 21,8 ms |
| Año | 29,9 ms |

El plan de ejecución usa el índice compuesto `(user_id, fecha)` con `type=range`
y examina 314 filas de 10 006.

Frente al objetivo de 300 ms, el peor caso queda nueve veces por debajo, así que
**no se implementa caché**: sería complejidad e invalidación sin problema que
resolver.
