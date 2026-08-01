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
- **Commit relacionado:** `0ea0c82` — docs: documentar la preparación técnica de la fase 3B

### Versión 1.9

- **Fecha:** 27 de julio de 2026
- **Fase:** Fase 3B.1 — Inicialización segura del backend Laravel
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Inicializacion_Backend_v1.9.md`
  - `docs/06-architecture/cierre-fase-3b1-inicializacion-backend.md`
  - Estructura técnica dentro de `backend/` (esqueleto Laravel 13 depurado para API)
- **Archivos modificados:**
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
  - `docs/06-architecture/checklist-inicializacion-segura-3b1.md`
- **Decisiones agregadas:** D-103 a D-107
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-102; no alteran el inventario de 67 tablas)
- **Contenido:**
  - Configuración segura aplicada en `backend/`.
  - Depuración del esqueleto para backend exclusivamente API.
  - Versión efectiva: `laravel/framework` 13.22.0; PHP 8.5.1; Composer 2.10.2.
  - Drivers D-96, PostgreSQL como conexión predeterminada, UTC y locale `es`.
  - `routes/api.php` canónico y vacío; `/up` solo como salud técnica.
  - Eliminación de `User`, `UserFactory`, frontend Blade/Vite/NPM.
  - Política de `APP_KEY` local y archivos de entorno.
  - Checklist 3B.1 completado; pendientes trasladados a 3B.2+.
- **Commits técnicos:**
  - `798339e` — feat: inicializar backend Laravel 13 con configuración segura
  - `73298fd` — chore: depurar el esqueleto Laravel para backend API
- **Pendientes:** Fase 3B.2 — bases, roles y conexión PostgreSQL; tabla técnica `migrations`; pruebas contra `credimex_test`; migraciones de dominio y prueba de Sanctum en fases posteriores.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual técnico (instalación del backend API);
  - Guía de contribución (entorno local y secretos);
  - Manual del administrador (cuando exista autenticación).
- **Commit relacionado:** `2b538e1` — docs: documentar el cierre de la fase 3B.1

### Versión 2.0

- **Fecha:** 27 de julio de 2026
- **Fase:** Fase 3B.2 — Bases, roles, esquema y conexiones PostgreSQL
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`
  - `docs/06-architecture/cierre-fase-3b2-configuracion-postgresql.md`
- **Archivos modificados:**
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
  - `AGENTS.md`
  - `backend/README.md` (ajuste mínimo de estado)
  - `backend/config/database.php` (conexiones; configuración técnica de la misma fase)
  - `backend/.env.example` / `backend/.env.testing.example` (configuración técnica de la misma fase)
- **Decisiones agregadas:** D-108 a D-116
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-107; no alteran el inventario de 67 tablas)
- **Contenido:**
  - PostgreSQL 18.4 local: `credimex_dev` / `credimex_test` (propiedad `postgres`).
  - Esquema `credimex` por base; owners `credimex_owner` / `credimex_test_owner`.
  - Cuatro roles; aislamiento CONNECT; `search_path=credimex`; UTC; UTF8; ICU `es-MX`; SCRAM.
  - Laravel: `pgsql` + `pgsql_owner`; conexiones validadas vía Tinker.
  - Tabla técnica `migrations` instalada y sin privilegios para roles app.
  - Incidencia controlada: esquema vacío accidental en `postgres` eliminado sin CASCADE.
  - Sin migraciones ni tablas del dominio; fail-closed PHPUnit aún no implementado.
- **Pendientes:** Fase 3B.3 — migraciones de seguridad y catálogos; prueba de Sanctum; guarda fail-closed; privilegios explícitos por tabla.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual técnico (instalación PostgreSQL y conexiones);
  - Guía de contribución (roles, secretos, migraciones con `pgsql_owner`);
  - Manual del administrador (cuando existan operaciones de dominio).
- **Commit técnico relacionado:** `a845485` — feat: configurar conexiones PostgreSQL por entorno
- **Commit relacionado:** `f54b7c2` — docs: documentar el cierre de la fase 3B.2

### Versión 2.1

- **Fecha:** 28 de julio de 2026
- **Fase:** Fase 3B.3 en curso — subfase 3B.3.0A (guarda PHPUnit
  fail-closed para PostgreSQL)
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`
  - `docs/06-architecture/cierre-subfase-3b3-0a-guarda-postgresql-fail-closed.md`
- **Archivos modificados:**
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
  - `AGENTS.md`
  - `backend/README.md` (ajuste breve de pruebas)
- **Decisiones agregadas:** D-117
- **Decisiones reemplazadas:** ninguna (complementa D-113 / D-100; no
  altera D-01 a D-116 ni el inventario de 67 tablas)
- **Contenido:**
  - Guarda `PostgreSqlTestSafetyGuard` integrada en
    `Tests\TestCase::setUpTraits()` antes del padre.
  - Validación fail-closed de entorno `testing`, conexiones `pgsql` /
    `pgsql_owner`, base `credimex_test`, usuarios
    `credimex_test_app` / `credimex_test_owner`, esquema y
    `search_path` `credimex`.
  - Rechazo de `RefreshDatabase`, `DatabaseMigrations` y
    `DatabaseTruncation`.
  - Errores de inspección sanitizados.
  - Suite: 19 pruebas / 58 assertions.
  - Sin grants, sin `DB_APP_ROLE`, sin migraciones ni tablas del
    dominio.
- **Pendientes:** subfase 3B.3.0B (configuración y helper seguro de
  grants); trait propio con `pgsql_owner`; migraciones de seguridad y
  catálogos; prueba de Sanctum. Candidatas D-118 a D-122 no aprobadas.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente
  para:
  - Manual técnico (ejecución de pruebas y entorno PostgreSQL);
  - Guía de contribución (traits prohibidos y guarda fail-closed).
- **Commit técnico relacionado:** `51527f7` — test: implementar guarda PostgreSQL fail-closed
- **Commit relacionado:** `45efb66` — docs: documentar el cierre de la subfase 3B.3.0A

### Versión 2.2

- **Fecha:** 28 de julio de 2026
- **Fase:** Fase 3B.3 en curso — subfase 3B.3.0B (helper seguro de
  privilegios PostgreSQL)
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Helper_Privilegios_PostgreSQL_v2.2.md`
  - `docs/06-architecture/cierre-subfase-3b3-0b-helper-privilegios-postgresql.md`
- **Archivos modificados:**
  - `docs/00-index.md`
  - `docs/00-historial-documentacion.md`
  - `AGENTS.md`
  - `backend/README.md`
- **Decisiones agregadas:** D-118 y D-119
- **Decisiones reemplazadas:** ninguna (complementan D-116 / D-117; no
  alteran D-01 a D-117 ni el inventario de 67 tablas)
- **Contenido:**
  - Helper `PostgreSqlGrantManager` con API grant/revoke de tablas y
    secuencias.
  - Resolución de rol app por entorno vía
    `config('credimex.database.app_role')`.
  - Validación fail-closed de entorno, conexiones, rol, esquema y
    `search_path`.
  - Protección de `migrations` / `migrations_id_seq`; sin
    `ALTER DEFAULT PRIVILEGES`.
  - Suite: 66 pruebas / 193 assertions.
  - Sin migraciones ni tablas del dominio; sin GRANT/REVOKE reales de
    negocio.
- **Pendientes:** subfase 3B.3.1 — migraciones de roles, permisos y
  rol_permisos; subfase 3B.3.2 — usuarios y dispositivos; D-120 a
  D-122 siguen como candidatas (D-121 divide su implementación en
  3B.3.1 y 3B.3.2). Sin tag. Sin merge.
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente
  para:
  - Manual técnico (privilegios PostgreSQL y migraciones);
  - Guía de contribución (uso obligatorio del helper).
- **Commit técnico relacionado:** `4cd8847` — feat: implementar helper seguro de privilegios PostgreSQL
- **Commit relacionado:** `55b0960` — docs: documentar el cierre de la subfase 3B.3.0B
