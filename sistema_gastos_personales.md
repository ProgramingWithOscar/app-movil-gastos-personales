# Sistema de Gestión de Gastos Personales
## Documento de análisis funcional y técnico

**Plataformas:** Aplicación móvil + aplicación web/escritorio responsive  
**Objetivo:** Centralizar ingresos, gastos, presupuestos, metas y análisis financiero personal.

---

# 1. Visión general

El sistema permitirá a una persona registrar y consultar su actividad financiera desde cualquier dispositivo. La información deberá sincronizarse entre móvil y PC, ofreciendo una experiencia consistente y datos actualizados.

La aplicación estará enfocada inicialmente en finanzas personales, pero su arquitectura debe permitir futuras ampliaciones, como cuentas compartidas, integración bancaria y funcionalidades asistidas por inteligencia artificial.

## Objetivos principales

- Registrar ingresos y gastos rápidamente.
- Conocer cuánto dinero se tiene disponible.
- Clasificar automáticamente o manualmente los movimientos.
- Controlar presupuestos por categoría.
- Analizar hábitos financieros mediante gráficos e indicadores.
- Crear metas de ahorro.
- Consultar y gestionar la información desde móvil y PC.
- Generar reportes por períodos.
- Proteger adecuadamente la información financiera.

---

# 2. Usuarios y roles

## 2.1 Usuario estándar

Es el propietario de su información financiera.

Puede:

- Gestionar su perfil.
- Crear cuentas financieras.
- Registrar ingresos, gastos y transferencias.
- Crear categorías.
- Definir presupuestos.
- Crear metas de ahorro.
- Consultar reportes.
- Configurar recordatorios.
- Exportar sus datos.

## 2.2 Administrador

Rol pensado para la administración futura de la plataforma.

Puede:

- Gestionar usuarios.
- Consultar métricas generales anonimizadas.
- Gestionar configuraciones globales.
- Administrar catálogos predeterminados.
- Revisar errores y actividad técnica.

> En una primera versión personal, este módulo puede no ser necesario.

---

# 3. Módulos del sistema

## Módulo 1. Autenticación y gestión de usuarios

### Funcionalidades

- Registro mediante correo y contraseña.
- Inicio de sesión.
- Cierre de sesión.
- Recuperación de contraseña.
- Cambio de contraseña.
- Inicio de sesión con proveedores externos en el futuro.
- Gestión de sesiones y dispositivos.
- Eliminación de cuenta.
- Verificación de correo electrónico.

### Datos del usuario

- Nombre.
- Correo electrónico.
- Foto de perfil.
- Moneda principal.
- País o región.
- Zona horaria.
- Preferencias de notificaciones.
- Tema visual.
- Fecha de creación.

---

## Módulo 2. Dashboard principal

Será la pantalla principal después del inicio de sesión.

### Información principal

- Saldo total.
- Ingresos del período.
- Gastos del período.
- Balance del período.
- Dinero disponible.
- Presupuesto utilizado.
- Próximos pagos.
- Metas de ahorro.
- Últimos movimientos.

### Indicadores

- Comparación de gastos frente al período anterior.
- Categoría con mayor gasto.
- Promedio diario de gasto.
- Porcentaje de presupuesto consumido.
- Evolución del saldo.

### Acciones rápidas

- Agregar gasto.
- Agregar ingreso.
- Transferir dinero.
- Ver movimientos.
- Crear presupuesto.

---

## Módulo 3. Cuentas financieras

Representa los lugares donde el usuario tiene o administra dinero.

### Tipos de cuentas

- Efectivo.
- Cuenta bancaria.
- Cuenta de ahorro.
- Cuenta corriente.
- Billetera digital.
- Tarjeta de crédito.
- Tarjeta débito.
- Cuenta de inversión.
- Otra.

### Funcionalidades

- Crear cuenta.
- Editar cuenta.
- Archivar cuenta.
- Definir saldo inicial.
- Consultar saldo actual.
- Consultar historial.
- Transferir dinero entre cuentas.
- Configurar color o icono.
- Establecer una cuenta como favorita.

### Datos principales

- Nombre.
- Tipo.
- Moneda.
- Saldo inicial.
- Saldo actual calculado.
- Fecha de creación.
- Estado.
- Color o icono.

---

## Módulo 4. Gestión de ingresos

Permite registrar todo dinero que entra al sistema financiero del usuario.

### Ejemplos

- Salario.
- Trabajo independiente.
- Venta.
- Regalo.
- Reembolso.
- Intereses.
- Otros.

### Funcionalidades

- Registrar ingreso.
- Editar ingreso.
- Eliminar o anular ingreso.
- Asignar categoría.
- Asociar cuenta.
- Añadir descripción.
- Adjuntar comprobante en el futuro.
- Programar ingresos recurrentes.

### Datos

- Monto.
- Fecha.
- Categoría.
- Cuenta.
- Descripción.
- Etiquetas.
- Estado.
- Fecha de creación.

---

## Módulo 5. Gestión de gastos

Es uno de los módulos principales del sistema.

### Ejemplos de categorías

- Alimentación.
- Transporte.
- Vivienda.
- Servicios.
- Educación.
- Salud.
- Entretenimiento.
- Compras.
- Tecnología.
- Suscripciones.
- Deudas.
- Mascotas.
- Otros.

### Funcionalidades

- Registrar gasto rápidamente.
- Editar gasto.
- Eliminar gasto.
- Dividir un gasto entre categorías.
- Añadir notas.
- Registrar comercio o establecimiento.
- Añadir etiquetas.
- Adjuntar comprobante en el futuro.
- Repetir un gasto.
- Registrar gastos recurrentes.
- Buscar y filtrar gastos.

### Datos

- Monto.
- Fecha y hora.
- Categoría.
- Subcategoría.
- Cuenta.
- Comercio.
- Descripción.
- Etiquetas.
- Tipo de movimiento.
- Estado.

---

## Módulo 6. Transferencias

Permite mover dinero entre cuentas sin considerarlo un ingreso o gasto real.

### Ejemplo

Transferir dinero desde una cuenta bancaria hacia una billetera digital.

### Funcionalidades

- Seleccionar cuenta origen.
- Seleccionar cuenta destino.
- Definir monto.
- Registrar comisión.
- Registrar fecha.
- Añadir descripción.

La comisión, si existe, puede registrarse automáticamente como gasto.

---

## Módulo 7. Categorías y subcategorías

El usuario podrá organizar sus movimientos de acuerdo con sus necesidades.

### Funcionalidades

- Crear categorías.
- Editar categorías.
- Eliminar categorías.
- Crear subcategorías.
- Seleccionar iconos.
- Seleccionar colores.
- Ordenar categorías.
- Crear categorías personalizadas.

### Estructura sugerida

**Gastos**
- Alimentación
  - Supermercado
  - Restaurantes
  - Domicilios
- Transporte
  - Combustible
  - Transporte público
  - Taxi
- Entretenimiento
  - Cine
  - Videojuegos
  - Eventos

**Ingresos**
- Salario.
- Freelance.
- Negocio.
- Inversiones.
- Otros.

---

## Módulo 8. Presupuestos

Permite definir límites de gasto.

### Tipos de presupuesto

- Mensual.
- Semanal.
- Personalizado.
- Por categoría.
- General.

### Ejemplo

Presupuesto mensual de alimentación: $500.000.

El sistema mostrará:

- Gastado.
- Disponible.
- Porcentaje utilizado.
- Días restantes.
- Proyección de gasto.

### Alertas

- 50 % utilizado.
- 80 % utilizado.
- 100 % alcanzado.
- Presupuesto superado.

Los porcentajes deberán poder configurarse.

---

## Módulo 9. Metas de ahorro

Permite crear objetivos financieros.

### Ejemplos

- Comprar un computador.
- Realizar un viaje.
- Crear fondo de emergencia.
- Comprar un celular.

### Datos

- Nombre.
- Monto objetivo.
- Monto actual.
- Fecha objetivo opcional.
- Imagen o icono.
- Cuenta asociada opcional.
- Prioridad.

### Funcionalidades

- Crear meta.
- Añadir dinero.
- Retirar dinero.
- Consultar progreso.
- Calcular cuánto falta.
- Calcular aporte sugerido según la fecha objetivo.
- Marcar meta como completada.

---

## Módulo 10. Movimientos recurrentes

Automatiza ingresos y gastos que ocurren periódicamente.

### Ejemplos

- Salario mensual.
- Arriendo.
- Netflix.
- Internet.
- Gimnasio.
- Suscripciones.

### Configuración

- Tipo de movimiento.
- Frecuencia.
- Fecha inicial.
- Fecha final opcional.
- Próxima ejecución.
- Cuenta.
- Categoría.
- Monto.
- Descripción.

### Frecuencias

- Diaria.
- Semanal.
- Quincenal.
- Mensual.
- Anual.
- Personalizada.

---

## Módulo 11. Calendario financiero

Vista temporal de movimientos.

### Funcionalidades

- Ver gastos por día.
- Ver ingresos por día.
- Ver pagos próximos.
- Ver movimientos recurrentes.
- Navegar entre meses.
- Consultar total diario.
- Crear movimientos desde una fecha específica.

---

## Módulo 12. Reportes y analítica

Transforma los datos financieros en información útil.

### Reportes

- Gastos por categoría.
- Ingresos por categoría.
- Gastos por período.
- Evolución de ingresos.
- Evolución de gastos.
- Balance mensual.
- Comparación entre períodos.
- Estado de presupuestos.
- Progreso de metas.

### Filtros

- Fecha.
- Cuenta.
- Categoría.
- Subcategoría.
- Etiquetas.
- Tipo de movimiento.
- Monto mínimo y máximo.

### Visualizaciones

- Gráfico de pastel o dona.
- Gráfico de barras.
- Gráfico de líneas.
- Indicadores numéricos.
- Comparativas.

### Exportación

- CSV.
- Excel.
- PDF en una versión futura.

---

## Módulo 13. Búsqueda y filtros

Permitirá encontrar movimientos rápidamente.

### Buscar por

- Nombre del comercio.
- Descripción.
- Categoría.
- Etiqueta.

### Filtrar por

- Período.
- Cuenta.
- Tipo.
- Categoría.
- Rango de monto.

### Ordenar por

- Más reciente.
- Más antiguo.
- Mayor monto.
- Menor monto.

---

## Módulo 14. Notificaciones y recordatorios

### Casos de uso

- Recordatorio de pago.
- Presupuesto próximo al límite.
- Presupuesto superado.
- Movimiento recurrente próximo.
- Meta alcanzada.
- Recordatorio para registrar gastos.

### Configuración

El usuario podrá activar o desactivar cada tipo de notificación.

---

## Módulo 15. Configuración

### Opciones

- Moneda principal.
- Formato de fecha.
- Zona horaria.
- Idioma.
- Tema claro.
- Tema oscuro.
- Notificaciones.
- Categorías predeterminadas.
- Exportación de datos.
- Eliminación de datos.
- Gestión de cuenta.

---

# 4. Requisitos funcionales principales

## RF-01

El sistema debe permitir a un usuario registrarse e iniciar sesión.

## RF-02

El sistema debe permitir crear una o varias cuentas financieras.

## RF-03

El usuario debe poder registrar ingresos.

## RF-04

El usuario debe poder registrar gastos.

## RF-05

El usuario debe poder realizar transferencias entre cuentas.

## RF-06

El sistema debe calcular automáticamente el saldo de cada cuenta.

## RF-07

El usuario debe poder crear categorías y subcategorías personalizadas.

## RF-08

El usuario debe poder definir presupuestos.

## RF-09

El sistema debe informar el progreso de cada presupuesto.

## RF-10

El usuario debe poder crear metas de ahorro.

## RF-11

El usuario debe poder crear movimientos recurrentes.

## RF-12

El sistema debe mostrar reportes y estadísticas.

## RF-13

El usuario debe poder buscar y filtrar movimientos.

## RF-14

El sistema debe sincronizar los datos entre móvil y PC.

## RF-15

El sistema debe permitir exportar los datos.

---

# 5. Requisitos no funcionales

## Seguridad

- Contraseñas almacenadas mediante hash seguro.
- Comunicación mediante HTTPS.
- Autenticación basada en tokens o sesiones seguras.
- Control de acceso para impedir consultar información de otros usuarios.
- Validación de datos en frontend y backend.
- Protección contra ataques comunes.
- Copias de seguridad.

## Rendimiento

- Las operaciones comunes deben responder rápidamente.
- Las consultas deben estar paginadas.
- Los gráficos deben cargar de forma eficiente.
- La aplicación debe funcionar correctamente con grandes cantidades de movimientos.

## Usabilidad

- Registro de gastos en pocos pasos.
- Diseño responsive.
- Interfaz clara.
- Navegación consistente entre móvil y PC.
- Accesibilidad básica.

## Disponibilidad

- Sincronización confiable.
- Manejo de errores de conexión.
- Posibilidad futura de funcionamiento offline.

## Escalabilidad

La arquitectura debe permitir agregar:

- Cuentas compartidas.
- Integraciones bancarias.
- OCR para facturas.
- Inteligencia artificial.
- Soporte para múltiples monedas.

---

# 6. Arquitectura recomendada

## Opción recomendada: arquitectura API + clientes separados

### Backend

Responsable de:

- Autenticación.
- Reglas de negocio.
- Gestión de movimientos.
- Cálculo de balances.
- Presupuestos.
- Reportes.
- Notificaciones.
- API.

### Base de datos

Responsable de almacenar:

- Usuarios.
- Cuentas.
- Categorías.
- Movimientos.
- Presupuestos.
- Metas.
- Configuraciones.

### Aplicación web

Optimizada para:

- PC.
- Tablets.
- Navegadores móviles.

### Aplicación móvil

Optimizada para:

- Android.
- iOS.

### Comunicación

```text
Web / PC ───────┐
                │
                ▼
             API Backend
                │
                ▼
          Base de datos
                ▲
                │
Móvil ──────────┘
```

---

# 7. Stack tecnológico sugerido

## Alternativa A: alineada con experiencia web moderna

### Frontend web

- Nuxt o Vue.
- TypeScript.
- Tailwind CSS.
- PWA.

### Aplicación móvil

- Flutter.
- Dart.

### Backend

- Laravel.
- PHP.
- Laravel Sanctum o una solución equivalente para autenticación.

### Base de datos

- PostgreSQL o MySQL.

### Infraestructura

- Docker.
- Nginx.
- CI/CD.
- Almacenamiento compatible con S3 para archivos futuros.

## Alternativa B: ecosistema JavaScript/TypeScript

- Web: Next.js.
- Móvil: React Native.
- Backend: NestJS.
- Base de datos: PostgreSQL.

### Recomendación inicial

Para un desarrollo individual, se recomienda elegir un stack que permita reutilizar conocimientos existentes y mantener una arquitectura sencilla. Flutter + Laravel + PostgreSQL/MySQL es una combinación sólida para una aplicación móvil y web con un backend centralizado.

---

# 8. Modelo de datos inicial

## users

- id
- name
- email
- password
- avatar
- default_currency
- timezone
- created_at
- updated_at

## accounts

- id
- user_id
- name
- type
- currency
- initial_balance
- current_balance
- icon
- color
- is_archived
- created_at
- updated_at

## categories

- id
- user_id
- parent_id
- name
- type
- icon
- color
- is_default
- created_at
- updated_at

## transactions

- id
- user_id
- account_id
- category_id
- type
- amount
- transaction_date
- description
- merchant
- status
- recurring_transaction_id
- created_at
- updated_at

### Valores posibles de type

- income
- expense
- transfer

## transfers

- id
- user_id
- source_account_id
- destination_account_id
- amount
- fee
- transaction_date
- description
- created_at

## budgets

- id
- user_id
- category_id
- amount
- period_type
- start_date
- end_date
- alert_percentage
- created_at
- updated_at

## savings_goals

- id
- user_id
- name
- target_amount
- current_amount
- target_date
- priority
- status
- created_at
- updated_at

## goal_contributions

- id
- goal_id
- account_id
- amount
- contribution_date
- note
- created_at

## recurring_transactions

- id
- user_id
- account_id
- category_id
- type
- amount
- frequency
- start_date
- end_date
- next_execution_date
- description
- is_active
- created_at
- updated_at

## notifications

- id
- user_id
- type
- title
- body
- is_read
- created_at

---

# 9. Relaciones principales

```text
User
 ├── Accounts
 ├── Categories
 ├── Transactions
 ├── Budgets
 ├── Savings Goals
 ├── Recurring Transactions
 └── Notifications

Account
 ├── Transactions
 └── Goal Contributions

Category
 ├── Parent Category
 ├── Subcategories
 ├── Transactions
 └── Budgets
```

---

# 10. Reglas de negocio importantes

1. Un gasto disminuye el saldo de una cuenta.
2. Un ingreso aumenta el saldo de una cuenta.
3. Una transferencia disminuye el saldo de la cuenta origen y aumenta el saldo de la cuenta destino.
4. Una transferencia no debe duplicarse como ingreso y gasto en las estadísticas generales.
5. Un presupuesto solo debe considerar los gastos correspondientes a su categoría y período.
6. Un usuario solo puede consultar y modificar sus propios datos.
7. El saldo actual debe poder recalcularse a partir de los movimientos para garantizar integridad.
8. Los movimientos recurrentes deben evitar duplicaciones.
9. Una categoría no debe eliminarse si existen movimientos asociados sin una estrategia de reasignación.
10. El sistema debe conservar fechas y zonas horarias correctamente.

---

# 11. Pantallas principales

## Móvil

### Navegación inferior

- Inicio.
- Movimientos.
- Botón agregar.
- Presupuestos.
- Más.

### Pantallas

1. Inicio.
2. Lista de movimientos.
3. Crear movimiento.
4. Detalle de movimiento.
5. Cuentas.
6. Categorías.
7. Presupuestos.
8. Metas.
9. Reportes.
10. Calendario.
11. Notificaciones.
12. Perfil y configuración.

## PC / Web

### Menú lateral

- Dashboard.
- Movimientos.
- Cuentas.
- Presupuestos.
- Metas.
- Reportes.
- Calendario.
- Configuración.

La versión web puede aprovechar mejor tablas, filtros avanzados y gráficos más amplios.

---

# 12. Flujo principal de usuario

```text
Registro / Inicio de sesión
          ↓
       Dashboard
          ↓
    Registrar movimiento
          ↓
Seleccionar ingreso o gasto
          ↓
Monto + cuenta + categoría
          ↓
         Guardar
          ↓
Actualizar saldo, presupuesto y estadísticas
```

---

# 13. MVP recomendado

Para evitar construir una aplicación gigantesca desde el primer día, la primera versión debería incluir:

## Fase 1: núcleo

- [ ] Registro e inicio de sesión.
- [ ] Perfil de usuario.
- [ ] Cuentas financieras.
- [ ] Categorías.
- [ ] Registro de ingresos.
- [ ] Registro de gastos.
- [ ] Transferencias.
- [ ] Dashboard.
- [ ] Lista y filtros básicos de movimientos.
- [ ] Sincronización entre dispositivos.

## Fase 2: control financiero

- [ ] Presupuestos.
- [ ] Metas de ahorro.
- [ ] Movimientos recurrentes.
- [ ] Notificaciones.
- [ ] Reportes y gráficos.
- [ ] Exportación CSV.

## Fase 3: funcionalidades avanzadas

- [ ] Modo offline.
- [ ] Adjuntos y comprobantes.
- [ ] Exportación Excel.
- [ ] Múltiples monedas.
- [ ] OCR de facturas.
- [ ] Integración bancaria.
- [ ] Cuentas compartidas.
- [ ] Inteligencia artificial.

---

# 14. Funcionalidades futuras con IA

La IA debe ser una mejora y no un requisito para el MVP.

## Posibles funcionalidades

### Clasificación automática

El usuario escribe:

> “Almuerzo en restaurante por 25.000”

El sistema podría sugerir:

- Tipo: gasto.
- Categoría: alimentación.
- Subcategoría: restaurantes.
- Monto: 25.000.

### Consultas en lenguaje natural

Ejemplos:

- “¿En qué gasté más este mes?”
- “¿Cuánto gasté en comida durante julio?”
- “¿Puedo gastar 300.000 más este mes sin superar mi presupuesto?”
- “Compara mis gastos de este mes con los del mes anterior.”

### Detección de patrones

- Gastos inusualmente altos.
- Aumento progresivo en una categoría.
- Suscripciones olvidadas.
- Proyección de gastos.

### Recomendaciones

- Sugerencias de ahorro.
- Alertas de comportamiento financiero.
- Estimación de presupuesto para el siguiente período.

---

# 15. API inicial sugerida

## Autenticación

- POST /api/auth/register
- POST /api/auth/login
- POST /api/auth/logout
- POST /api/auth/forgot-password
- POST /api/auth/reset-password

## Usuario

- GET /api/user
- PUT /api/user
- DELETE /api/user

## Cuentas

- GET /api/accounts
- POST /api/accounts
- GET /api/accounts/{id}
- PUT /api/accounts/{id}
- DELETE /api/accounts/{id}

## Categorías

- GET /api/categories
- POST /api/categories
- PUT /api/categories/{id}
- DELETE /api/categories/{id}

## Movimientos

- GET /api/transactions
- POST /api/transactions
- GET /api/transactions/{id}
- PUT /api/transactions/{id}
- DELETE /api/transactions/{id}

## Transferencias

- POST /api/transfers

## Presupuestos

- GET /api/budgets
- POST /api/budgets
- PUT /api/budgets/{id}
- DELETE /api/budgets/{id}

## Metas

- GET /api/goals
- POST /api/goals
- GET /api/goals/{id}
- PUT /api/goals/{id}
- DELETE /api/goals/{id}
- POST /api/goals/{id}/contributions

## Dashboard y reportes

- GET /api/dashboard
- GET /api/reports/expenses
- GET /api/reports/income
- GET /api/reports/balance
- GET /api/reports/categories

---

# 16. Métricas útiles

## Financieras

- Total de ingresos.
- Total de gastos.
- Balance.
- Tasa de ahorro.
- Promedio de gasto diario.
- Categoría de mayor gasto.
- Presupuesto restante.

## Fórmulas

### Balance

```text
Balance = Ingresos - Gastos
```

### Tasa de ahorro

```text
Tasa de ahorro = (Ingresos - Gastos) / Ingresos × 100
```

Cuando los ingresos sean cero, la tasa debe manejarse adecuadamente para evitar divisiones inválidas.

---

# 17. Consideraciones para móvil y PC

## Principio principal

La misma información debe estar disponible en todas las plataformas.

## Móvil

Priorizar:

- Registro rápido.
- Botones grandes.
- Acciones frecuentes.
- Notificaciones.
- Uso cómodo con una mano.

## PC

Priorizar:

- Tablas.
- Filtros avanzados.
- Gráficos.
- Reportes.
- Gestión masiva de información.

## Sincronización

Cada modificación realizada desde un dispositivo debe reflejarse en los demás mediante el backend central.

---

# 18. Roadmap de desarrollo

## Sprint 1: Base del proyecto

- Configuración de repositorios.
- Backend.
- Base de datos.
- Autenticación.
- Diseño del sistema de navegación.

## Sprint 2: Finanzas básicas

- Cuentas.
- Categorías.
- Ingresos.
- Gastos.
- Cálculo de saldos.

## Sprint 3: Dashboard y movimientos

- Dashboard.
- Historial.
- Búsqueda.
- Filtros.
- Detalle de movimientos.

## Sprint 4: Funciones financieras

- Transferencias.
- Presupuestos.
- Alertas.

## Sprint 5: Objetivos y automatización

- Metas de ahorro.
- Movimientos recurrentes.
- Recordatorios.

## Sprint 6: Analítica

- Reportes.
- Gráficos.
- Comparaciones.
- Exportación.

## Sprint 7: Calidad

- Pruebas.
- Seguridad.
- Optimización.
- Manejo de errores.
- Ajustes de experiencia de usuario.

---

# 19. Prioridades

| Funcionalidad | Prioridad |
|---|---|
| Autenticación | Alta |
| Cuentas | Alta |
| Ingresos | Alta |
| Gastos | Alta |
| Categorías | Alta |
| Dashboard | Alta |
| Sincronización | Alta |
| Transferencias | Media |
| Presupuestos | Media |
| Metas | Media |
| Movimientos recurrentes | Media |
| Reportes avanzados | Media |
| Notificaciones | Media |
| Exportación | Media |
| IA | Baja inicialmente |
| OCR | Baja inicialmente |
| Integración bancaria | Baja inicialmente |

---

# 20. Definición final del MVP

La primera versión del sistema debe responder correctamente a esta pregunta:

> **¿Cuánto dinero tengo, cuánto he gastado, en qué lo he gastado y cuánto puedo gastar sin superar mi presupuesto?**

Si el sistema responde a esas preguntas de forma rápida, clara y sincronizada entre móvil y PC, el núcleo del producto estará bien construido.

---

# 21. Próximos pasos recomendados

1. Definir nombre e identidad del proyecto.
2. Crear wireframes de las pantallas principales.
3. Diseñar el modelo entidad-relación definitivo.
4. Definir el stack tecnológico final.
5. Crear la API y autenticación.
6. Implementar el módulo de cuentas.
7. Implementar ingresos y gastos.
8. Crear el dashboard.
9. Conectar la aplicación móvil.
10. Agregar presupuestos y reportes.
11. Realizar pruebas con datos reales.
12. Incorporar funciones avanzadas progresivamente.
