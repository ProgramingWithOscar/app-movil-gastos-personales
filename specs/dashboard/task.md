# Tareas — Dashboard principal

Plan de implementación de [`spec.md`](./spec.md).

Leyenda: `[ ]` pendiente · `[~]` en curso · `[x]` hecho

> Las fases B y C dependen de módulos que aún no existen. Están al final, sin
> estimar, para que se vea el destino sin bloquear lo que sí se puede hacer.

---

## Bloque 1 · Cálculos en el backend

- [x] **1.1** `App\Services\CalculadorPeriodos`: dado un tipo de período y la
      zona horaria del usuario, devuelve `[desde, hasta]` y el rango del período
      anterior. Aislado en su clase porque lo van a usar reportes y presupuestos.
- [x] **1.2** Soportar `periodo=personalizado` con `desde` y `hasta`, validando
      que `desde <= hasta` y que el rango no supere un año.
- [x] **1.3** `App\Services\ResumenGastos`: totales, agregación por categoría,
      promedio diario sobre **días transcurridos**, proyección y categoría
      dominante.
- [x] **1.4** Comparación con el período anterior, resolviendo los tres casos
      borde de la spec §5 (anterior en cero, ambos en cero, tendencia).
- [x] **1.5** Tests unitarios de `CalculadorPeriodos` (8 casos: zonas horarias,
      días transcurridos, período anterior de calendario, rangos inválidos).
      `ResumenGastos` se verificó con un script en vez de una suite.

**Listo cuando:** `ResumenGastos` devuelve números correctos para un mes en
curso, uno cerrado y uno sin movimientos. ✅

## Bloque 2 · Endpoint

- [x] **2.1** `DashboardController@__invoke` en `GET /api/dashboard`, detrás de
      `auth:sanctum`.
- [x] **2.2** `DashboardRequest` validando `periodo`, `desde` y `hasta`.
- [x] **2.3** `DashboardResource` con la forma exacta de la spec §4.
- [x] **2.4** Incluir los movimientos recientes en la misma respuesta. Se hizo
      configurable con `?movimientos=` (por defecto 10, máximo 50): la app ya
      pide 20 desde que la lista tiene scroll propio.
- [x] **2.5** `GET /api/gastos/resumen` eliminado junto con `GastoController@resumen`,
      una vez que la app pasó a `GET /api/dashboard`.
- [x] **2.6** Comprobación por `curl` de un período con datos, uno vacío y uno
      personalizado.

**Listo cuando:** una sola petición devuelve todo lo que pinta la pantalla. ✅

## Bloque 3 · Indicadores en la app

- [x] **3.1** Modelos TypeScript del nuevo contrato (`Dashboard`, `Comparacion`,
      `IndicadorPeriodo`).
- [x] **3.2** `DashboardService` reemplazando las dos llamadas actuales de
      `HomePage`.
- [x] **3.3** Componente de tarjeta de indicador reutilizable: etiqueta, valor,
      icono y color según tendencia.
- [x] **3.4** Fila de tres indicadores: variación, promedio diario y categoría
      dominante.
- [x] **3.5** Que "baja" en gastos se pinte en verde y "sube" en rojo — al revés
      de lo intuitivo en finanzas, y por eso fácil de equivocar.
- [x] **3.6** Mostrar "sin referencia" cuando `variacion_porcentual` sea `null`,
      nunca un porcentaje inventado.

**Listo cuando:** se cumplen los criterios 5 y 6 de la spec §8. ✅

## Bloque 4 · Estados y comportamiento

- [x] **4.1** Esqueletos de carga con la forma del contenido real, en lugar del
      spinner actual.
- [x] **4.2** Estado vacío distinguiendo "nunca has registrado nada" de "no hay
      gastos en este período".
- [x] **4.3** Estado de error con reintentar, conservando los últimos datos.
- [x] **4.4** Recargar al volver a la pestaña tras registrar un movimiento
      (`ionViewWillEnter` o el evento del servicio de acciones).
- [x] **4.5** Recordar el período elegido en el dispositivo.
- [x] **4.6** Animar la transición de la dona al cambiar de período.

**Listo cuando:** se cumplen los criterios 7, 8 y 11 de la spec §8. ✅

## Bloque 5 · Rendimiento

- [x] **5.1** `CargaDemoSeeder`: 10 000 movimientos repartidos en dos años para
      el usuario `carga@gastos.test`. Se ejecuta a mano:
      `php artisan db:seed --class=CargaDemoSeeder`.
- [x] **5.2** Medido con 10 006 filas en la tabla (3 215 en el año consultado),
      5 corridas por período:

      | Período | Tiempo medio |
      |---|---|
      | Día     | 21,2 ms |
      | Semana  | 33,1 ms |
      | Mes     | 21,8 ms |
      | Año     | 29,9 ms |

      El objetivo era 300 ms. El peor caso quedó **9 veces por debajo**.

- [x] **5.3** `EXPLAIN` de la agregación por categoría:

      ```
      type=range | key=gastos_user_id_fecha_index | key_len=11
      rows=314 | filtered=100 | Extra=Using index condition; Using temporary
      ```

      Usa el índice compuesto y examina 314 filas de 10 006. El `Using temporary`
      es del `GROUP BY` sobre ese subconjunto ya filtrado, no un escaneo completo.

- [x] **5.4** **No se implementa caché.** La condición era superar los 300 ms y
      no se acerca. Cachear ahora solo añadiría una fuente de datos obsoletos y
      lógica de invalidación sin resolver ningún problema real.

**Listo cuando:** hay una medición real anotada, no una suposición. ✅

## Bloque 6 · Cierre

- [x] **6.1** Los 12 criterios repasados uno por uno, con la evidencia de cada
      uno anotada en la spec §9. Todos se cumplen.
- [x] **6.2** `GET /api/dashboard` documentado en el README, junto con el resto
      de la API, incluidas las trampas del contrato (`variacion_porcentual`
      nulo, promedio sobre días transcurridos, claves futuras).
- [x] **6.3** Las tres decisiones resueltas y anotadas en la spec §11: un solo
      endpoint, el selector de Ingresos se queda visible con aviso, y el período
      se recuerda entre sesiones.

**Listo cuando:** la Fase A queda cerrada y documentada. ✅

---

## Fase B · Ingresos y balance

El Módulo 4 (ingresos) se implementó para desbloquear esta fase. La tabla
`gastos` pasó a llamarse **`movimientos`** con una columna `tipo`, siguiendo el
modelo del documento (§8), que trata ingresos, gastos y transferencias como el
mismo tipo de registro.

- [x] Ingresos del período y balance.
- [x] Tasa de ahorro: `(ingresos - gastos) / ingresos × 100`. Con ingresos en
      cero devuelve `null` y la app dice "Sin ingresos registrados": mostrar 0 %
      haría creer que no se ahorró, cuando en realidad no hay base de cálculo.
- [x] El selector Gastos / Ingresos cambia de verdad la vista: total, dona,
      categorías e indicadores.
- [x] Separar el criterio de color: en gastos bajar es bueno, en ingresos subir.
      Lo decide `tonoSegunTendencia()` según el tipo visible.
- [x] Catálogo de categorías propio para ingresos (salario, freelance, negocio,
      inversiones, regalo, otros).
- [x] La hoja de acciones permite registrar ingresos, no solo gastos.

- [x] Saldo total y dinero disponible. Desbloqueado por el
      [Módulo 3 (cuentas)](../cuentas/task.md), Bloque 4.

Pendiente:

- [ ] Evolución del saldo. Necesita una serie temporal; encaja mejor en el
      Módulo 12 (reportes) que aquí.

## Fase C · Presupuestos

El Módulo 8 (presupuestos) se implementó para desbloquear esta parte.

- [x] Presupuesto consumido con barra, disponible y días restantes.
- [x] Estado calculado en el servidor: `al_dia`, `cerca`, `en_riesgo`,
      `superado`. El valor está en `en_riesgo`: avisa *antes* de pasarse, cuando
      al ritmo actual el dinero se acaba antes que el período. Enterarse cuando
      ya se superó no permite corregir nada.
- [x] Umbral de aviso configurable por presupuesto (el documento lo pide
      expresamente en §3, Módulo 8; no un 80 % fijo).
- [x] Presupuesto general (sin categoría) y por categoría, conviviendo.
- [x] Pantalla propia en la pestaña Presupuestos: crear, editar y eliminar.
- [x] En el dashboard solo se muestran los que piden atención; el resto vive en
      su pestaña.

**Decisión:** cada presupuesto se evalúa contra **su propio período**, no contra
el que muestra el dashboard. Un tope mensual mirado desde la vista de "día"
daría un porcentaje sin significado.

Pendiente, bloqueado por los Módulos 9 y 10:

- [ ] Próximos pagos (recurrentes).
- [ ] Progreso de metas de ahorro.
- [ ] Acción rápida de transferencia.

---

## Riesgos

1. **Coherencia entre números.** Si el total del encabezado y la suma de las
   categorías no cuadran, la pantalla pierde credibilidad entera. Por eso todo
   se calcula y se redondea en un único sitio.
2. **Zonas horarias.** Es el fallo más silencioso: nadie lo nota hasta que
   alguien registra un gasto de noche y aparece en el día equivocado.
3. **Crecer con módulos que no existen.** La forma de la respuesta debe admitir
   claves nuevas sin romper la app; la app nunca debe asumir que una clave está.
