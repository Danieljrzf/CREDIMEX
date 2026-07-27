# CREDIMEX — Historial de documentación

## Propósito

Este archivo registra las decisiones, correcciones, versiones y efectos sobre
los futuros manuales de CREDIMEX. Es el registro cronológico oficial de la
evolución documental del proyecto.

## Convenciones

Cada registro debe contener:

- fecha;
- versión;
- fase;
- archivos creados;
- archivos modificados;
- decisiones agregadas;
- decisiones reemplazadas;
- pendientes;
- impacto en manuales;
- commit relacionado.

## Historial inicial

### Versión 1.2

- **Fecha:** 24 de julio de 2026
- **Fase:** Línea base funcional y técnica
- **Archivos creados:**
  - `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`
- **Archivos modificados:** ninguno
- **Decisiones agregadas:** línea base de requerimientos y reglas de negocio
- **Decisiones reemplazadas:** ninguna
- **Contenido:**
  - Documento maestro del producto y desarrollo.
  - Requerimientos.
  - Casos de uso.
  - Procesos.
  - Arquitectura y estrategia de trabajo con Cursor.
- **Pendientes:** ambigüedades resueltas posteriormente en v1.3 y v1.4
- **Impacto en manuales:** base funcional para todos los manuales futuros
- **Commit relacionado:** pendiente de registrar

### Versión 1.3

- **Fecha:** 24 de julio de 2026
- **Fase:** Normalización documental
- **Archivos creados:**
  - `docs/00-index.md`
  - `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md`
  - `docs/01-requirements/cash-differences.md`
  - `docs/01-requirements/collector-workday.md`
  - `docs/02-use-cases/UC-24-fondo-a-cobrador.md`
  - `docs/02-use-cases/UC-25-caja-central.md`
  - `docs/02-use-cases/UC-26-castigar-credito.md`
- **Archivos modificados:**
  - `AGENTS.md`
- **Decisiones agregadas:** D-01 a D-20
- **Decisiones reemplazadas:** ninguna (precisan redacciones ambiguas del
  maestro v1.2)
- **Contenido:**
  - Decisiones D-01 a D-20.
  - Nuevos casos de uso UC-24, UC-25 y UC-26.
  - Diferencias de caja.
  - Jornada del cobrador.
  - Normalización de rutas documentales.
- **Pendientes:** reflejar las decisiones en el documento maestro cuando se
  autorice su actualización
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual del cobrador;
  - Manual del supervisor;
  - Manual del administrador;
  - Manual de operación de caja;
  - Manual técnico;
  - Guía rápida;
  - Manual de incidencias.
- **Commit relacionado:** pendiente de registrar

### Versión 1.4

- **Fecha:** 24 de julio de 2026
- **Fase:** Fase 3A — Modelo conceptual de datos
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`
  - `docs/04-database/catalogo-entidades.md`
  - `docs/04-database/catalogo-estados.md`
  - `docs/04-database/relaciones-conceptuales.md`
  - `docs/04-database/modelo-movimientos-financieros.md`
  - `docs/04-database/riesgos-y-decisiones-abiertas.md`
- **Archivos modificados:**
  - `docs/00-index.md`
- **Decisiones agregadas:** D-21 a D-32
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-20)
- **Contenido:**
  - Decisiones D-21 a D-32.
  - Catálogo conceptual de entidades.
  - Catálogo de estados.
  - Relaciones conceptuales.
  - Libro operativo de movimientos.
  - Riesgos y decisiones abiertas.
- **Pendientes:** los listados en
  `docs/04-database/riesgos-y-decisiones-abiertas.md`
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual del cobrador;
  - Manual del supervisor;
  - Manual del administrador;
  - Manual de operación de caja;
  - Manual técnico;
  - Guía rápida;
  - Manual de incidencias.
- **Commit relacionado:** `67e41fe` — docs: documentar el modelo conceptual y las decisiones de datos

### Versión 1.5

- **Fecha:** 24 de julio de 2026
- **Fase:** Fase 3A — Cierre del modelo conceptual financiero
- **Archivos creados:**
  - `docs/04-database/catalogo-operaciones-financieras.md`
  - `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md`
  - `docs/02-use-cases/UC-27-excepcion-permanencia-efectivo.md`
  - `docs/03-processes/puesta-en-marcha-carga-inicial.md`
- **Archivos modificados:**
  - `docs/04-database/catalogo-entidades.md`
  - `docs/04-database/catalogo-estados.md`
  - `docs/04-database/relaciones-conceptuales.md`
  - `docs/04-database/modelo-movimientos-financieros.md`
  - `docs/04-database/riesgos-y-decisiones-abiertas.md`
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
  - `docs/01-requirements/collector-workday.md`
  - `docs/01-requirements/cash-differences.md`
  - `docs/02-use-cases/UC-24-fondo-a-cobrador.md`
  - `docs/02-use-cases/UC-25-caja-central.md`
- **Decisiones agregadas:** D-33 a D-53
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-32; precisan
  D-17 respecto a movimientos brutos del desembolso)
- **Contenido:**
  - Catálogo financiero cerrado (20 códigos activos).
  - Movimientos brutos de desembolso (principal y comisión identificables).
  - Primer pago retenido como operación hija con `operacion_padre_id`.
  - Reconciliación diaria de atraso.
  - Excepción de cuarta noche (UC-27).
  - Jornada de caja central (apertura perezosa).
  - Carga inicial de saldos y proceso de puesta en marcha (D-51).
  - Reestructuración con carga de interés nuevo.
  - Contrapartes externas.
  - Entregas con diferencia (`CONFIRMADA_CON_DIFERENCIA`).
  - Motivo del fondo al cobrador (elimina `OrigenComercialFondo` del flujo
    ordinario).
  - Autorización administrativa exclusiva de ajustes de efectivo (D-52).
  - Política de modificación de días festivos (D-53).
- **Pendientes:** deuda de redacción del maestro v1.2.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual del cobrador;
  - Manual del supervisor;
  - Manual del administrador;
  - Manual de operación de caja;
  - Manual técnico;
  - Guía rápida;
  - Manual de incidencias.
- **Commit relacionado:** `b795cdf` — docs: cerrar el modelo financiero y las reglas operativas v1.5

### Versión 1.6

- **Fecha:** 25 de julio de 2026
- **Fase:** Fase 3A.2 — Modelo lógico de datos
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`
  - `docs/04-database/inventario-tablas-logicas.md`
  - `docs/04-database/diccionario-identidad-clientes-rutas.md`
  - `docs/04-database/diccionario-creditos-calendarios-pagos.md`
  - `docs/04-database/diccionario-cobranza-caja-entregas.md`
  - `docs/04-database/diccionario-libro-auditoria-procesos.md`
  - `docs/04-database/claves-relaciones-restricciones.md`
  - `docs/04-database/indices-conceptuales.md`
  - `docs/04-database/erd-conceptual.md`
  - `docs/04-database/riesgos-modelo-logico.md`
- **Archivos modificados:**
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
  - `docs/04-database/catalogo-entidades.md` (referencias cruzadas mínimas)
  - `docs/04-database/relaciones-conceptuales.md` (referencias cruzadas mínimas)
- **Decisiones agregadas:** D-54 a D-68
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-53; materializan el inventario lógico)
- **Contenido:**
  - Inventario lógico (67 tablas candidatas).
  - Diccionarios conceptuales de columnas por dominio.
  - Claves, relaciones y restricciones clasificadas (BD / APP / TX / REC).
  - Índices conceptuales.
  - ERD Mermaid en cuatro dominios.
  - Riesgos del modelo lógico.
- **Pendientes:** deuda de redacción del maestro v1.2; modelo físico PostgreSQL.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual técnico;
  - Manual de operación de caja;
  - Manual de incidencias;
  - Manual del administrador.
- **Commit relacionado:** `98712fa` — docs: definir el modelo lógico y el ERD v1.6

### Versión 1.7

- **Fecha:** 26 de julio de 2026
- **Fase:** Fase 3A.3 — Modelo físico preliminar PostgreSQL
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
  - `docs/04-database/modelo-fisico-postgresql.md`
  - `docs/04-database/identificadores-y-exposicion.md`
  - `docs/04-database/restricciones-fisicas-postgresql.md`
  - `docs/04-database/borrado-inactivacion-y-retencion.md`
  - `docs/04-database/sensibilidad-cifrado-y-logs.md`
  - `docs/04-database/convenciones-fisicas-y-orden-migraciones.md`
  - `docs/04-database/riesgos-modelo-fisico.md`
- **Archivos modificados:**
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
- **Decisiones agregadas:** D-69 a D-91
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-68; no alteran el inventario de 67 tablas)
- **Contenido:**
  - Estrategia BIGINT interno + UUID público selectivo (`id_publico`).
  - Exactamente 17 tablas con `id_publico`; UUIDv7 generado en backend.
  - Importes `BIGINT` en centavos; tasas `NUMERIC(9,6)` como factor.
  - `TIMESTAMPTZ`, `DATE` y zona IANA; snapshot en jornadas.
  - Restricciones directas, índices únicos parciales, transaccionales y reconciliadas.
  - Carga inicial lote + cuenta como APP + TX + REC (sin UNIQUE directo).
  - Concurrencia (`READ COMMITTED`) y orden de bloqueo.
  - Sensibilidad, cifrado, HMAC (`BYTEA`), versiones de clave y exclusión de logs.
  - Clasificación de borrado e inactivación (sin `deleted_at` generalizado).
  - 15 grupos **futuros** de migración (sin migraciones creadas).
  - Riesgos físicos pendientes de implementación.
- **Pendientes:** deuda de redacción del maestro v1.2; creación de migraciones cuando se autorice; diseño de KMS.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - administración de usuarios y accesos;
  - protección de información personal y bancaria;
  - funcionamiento de folios e identificadores públicos;
  - operación concurrente de pagos, entregas y cierres;
  - conservación, inactivación y trazabilidad de registros.
- **Commit relacionado:** `63bdd28` — docs: definir el modelo físico preliminar PostgreSQL v1.7

### Versión 1.8

- **Fecha:** 27 de julio de 2026
- **Fase:** Fase 3B.0 — Preparación técnica del backend y PostgreSQL
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
  - `docs/06-architecture/preparacion-tecnica-backend-postgresql.md`
  - `docs/06-architecture/checklist-inicializacion-segura-3b1.md`
  - `docs/06-architecture/estrategia-pruebas-postgresql.md`
- **Archivos modificados:**
  - `AGENTS.md`
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
- **Decisiones agregadas:** D-92 a D-102
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-91; no alteran el inventario de 67 tablas)
- **Contenido:**
  - Plataforma objetivo: Laravel ^13.0, PHP 8.5.x, Composer 2.x, PostgreSQL 18.x.
  - Entorno local validado: PHP 8.5.1, Composer 2.10.2, PostgreSQL 18.4, Git 2.50.
  - `backend/` como ubicación canónica del código Laravel.
  - Inicialización segura con `--remove-vcs --no-scripts`.
  - Eliminación de 3 migraciones predeterminadas y 9 tablas no autorizadas.
  - Tabla técnica `migrations` aceptada fuera de las 67 del dominio.
  - Drivers iniciales sin tablas técnicas (file/array/sync/null/local/log/stack).
  - Separación de roles PostgreSQL: `credimex_owner` (migraciones) y `credimex_app` (operación).
  - Bases `credimex_dev` y `credimex_test` con UTF8, ICU `es-MX` y UTC.
  - PK con `BIGINT GENERATED ALWAYS AS IDENTITY` (sin BIGSERIAL).
  - Autenticación pospuesta a prueba técnica de adaptación de Sanctum.
  - Pruebas exclusivas contra PostgreSQL; SQLite prohibido.
  - Manejo de entornos y secretos (`.env` local, `.env.example` versionado).
  - Scripts Composer: eliminar `setup` y `dev`; conservar `test` y hooks.
- **Pendientes:** ejecución de 3B.1 (inicialización de Laravel); creación de bases y roles PostgreSQL en 3B.2; prueba de Sanctum en 3B.3.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual técnico (instalación y configuración);
  - Manual del administrador (roles y permisos de base de datos);
  - Guía de contribución (entorno de desarrollo).
- **Commit relacionado:** pendiente de registrar
