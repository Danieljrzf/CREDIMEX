# CREDIMEX — Índice documental oficial

Este archivo es la **fuente canónica de rutas** de la documentación de CREDIMEX.
Ningún agente ni colaborador debe usar rutas de carpetas distintas a las declaradas aquí.

## Estado del proyecto

El proyecto se encuentra en **Fase 3B — Implementación del backend**.

La **Fase 3B.3** (migraciones de seguridad y catálogos; prueba de
Sanctum) está **en curso**.

La subfase **3B.3.0A** (guarda PHPUnit fail-closed) está
**técnicamente completada** (v2.1).

La subfase **3B.3.0B** (helper seguro de privilegios PostgreSQL) está
**técnicamente completada**. Versión documental **v2.2**.

La subfase **3B.3.1A** (harness owner-aware fail-closed para pruebas de
migraciones PostgreSQL) está **completada técnica y documentalmente**.
Versión documental vigente para D-120: **v2.3**.

La subfase **3B.3.1A.2** (extensión controlada de D-120 para escenarios
ordenados multiarchivo) está **completada técnica y documentalmente**.
Versión documental v2.4. D-121 queda aprobada con alcance refinado a la
base RBAC. El commit técnico es `9621ae1`; el commit documental v2.4 es
`0848014`. D-122 continúa pendiente.

La subfase **3B.3.1B** (migraciones RBAC iniciales) está **completada
técnica y documentalmente**. Versión documental vigente: **v2.5**. El
commit técnico es `c790e8d` —
`feat: implementar migraciones RBAC iniciales`. El commit documental
v2.5 es `214d1ba` —
`docs: documentar el cierre de la subfase 3B.3.1B`. D-122 continúa
pendiente.

El backend Laravel está conectado a PostgreSQL local con esquema
`credimex`, cuatro roles por entorno, tabla técnica `migrations`,
guarda fail-closed de pruebas, helper de privilegios
(`PostgreSqlGrantManager`) y harness D-120.

Las migraciones de `roles`, `permisos` y `rol_permisos` existen en el
repositorio. Esta subfase no las desplegó persistentemente en
desarrollo ni producción; D-120 las creó y revirtió en testing.

La siguiente subfase autorizada es **3B.3.1C — datos iniciales RBAC**.

## Documento maestro vigente

- `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`

Este es el único documento maestro válido. Cualquier referencia a
`CREDIMEX_Documento_Maestro_v1.2.md` u otras variantes de ruta es incorrecta.

## Carpetas oficiales

```text
docs/
├── 00-index.md          Índice canónico (este archivo)
├── 00-product/          Visión de producto
├── 01-requirements/     Requerimientos y decisiones resueltas
├── 02-use-cases/        Casos de uso
├── 03-processes/        Diagramas de procesos
├── 04-database/         Modelo de datos, ERD y diccionario
├── 05-api/              Contrato de API (OpenAPI)
├── 06-architecture/     Arquitectura y decisiones técnicas
├── 07-decisions/        Registros de decisión de arquitectura (ADR)
└── archive/             Material histórico o superado
```

## Documentos aprobados

| Documento | Ruta | Estado |
|---|---|---|
| Documento maestro de producto, requerimientos y desarrollo v1.2 | `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md` | Aprobado |
| Historial documental | `docs/00-historial-documentacion.md` | Aprobado |
| Decisiones resueltas v1.3 | `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md` | Aprobado |
| Decisiones de modelo de datos v1.4 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md` | Aprobado |
| Decisiones de modelo de datos v1.5 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md` | Aprobado |
| Diferencias de caja | `docs/01-requirements/cash-differences.md` | Aprobado |
| Jornada del cobrador | `docs/01-requirements/collector-workday.md` | Aprobado |
| Puesta en marcha y carga inicial | `docs/03-processes/puesta-en-marcha-carga-inicial.md` | Aprobado |
| UC-24 — Registrar fondo entregado al cobrador | `docs/02-use-cases/UC-24-fondo-a-cobrador.md` | Aprobado |
| UC-25 — Operar caja central | `docs/02-use-cases/UC-25-caja-central.md` | Aprobado |
| UC-26 — Castigar crédito | `docs/02-use-cases/UC-26-castigar-credito.md` | Aprobado |
| UC-27 — Excepción de permanencia de efectivo | `docs/02-use-cases/UC-27-excepcion-permanencia-efectivo.md` | Aprobado |
| Catálogo de entidades | `docs/04-database/catalogo-entidades.md` | Aprobado |
| Catálogo de estados | `docs/04-database/catalogo-estados.md` | Aprobado |
| Catálogo de operaciones financieras | `docs/04-database/catalogo-operaciones-financieras.md` | Aprobado |
| Relaciones conceptuales | `docs/04-database/relaciones-conceptuales.md` | Aprobado |
| Modelo de movimientos financieros | `docs/04-database/modelo-movimientos-financieros.md` | Aprobado |
| Riesgos y decisiones abiertas | `docs/04-database/riesgos-y-decisiones-abiertas.md` | Aprobado |
| Decisiones de modelo lógico v1.6 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md` | Aprobado |
| Inventario de tablas lógicas | `docs/04-database/inventario-tablas-logicas.md` | Aprobado |
| Diccionario identidad, clientes y rutas | `docs/04-database/diccionario-identidad-clientes-rutas.md` | Aprobado |
| Diccionario créditos, calendarios y pagos | `docs/04-database/diccionario-creditos-calendarios-pagos.md` | Aprobado |
| Diccionario cobranza, caja y entregas | `docs/04-database/diccionario-cobranza-caja-entregas.md` | Aprobado |
| Diccionario libro, auditoría y procesos | `docs/04-database/diccionario-libro-auditoria-procesos.md` | Aprobado |
| Claves, relaciones y restricciones | `docs/04-database/claves-relaciones-restricciones.md` | Aprobado |
| Índices conceptuales | `docs/04-database/indices-conceptuales.md` | Aprobado |
| ERD conceptual | `docs/04-database/erd-conceptual.md` | Aprobado |
| Riesgos del modelo lógico | `docs/04-database/riesgos-modelo-logico.md` | Aprobado |
| Decisiones de modelo físico v1.7 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md` | Aprobado |
| Modelo físico preliminar PostgreSQL | `docs/04-database/modelo-fisico-postgresql.md` | Aprobado |
| Identificadores y exposición | `docs/04-database/identificadores-y-exposicion.md` | Aprobado |
| Restricciones físicas PostgreSQL | `docs/04-database/restricciones-fisicas-postgresql.md` | Aprobado |
| Borrado, inactivación y retención | `docs/04-database/borrado-inactivacion-y-retencion.md` | Aprobado |
| Sensibilidad, cifrado y logs | `docs/04-database/sensibilidad-cifrado-y-logs.md` | Aprobado |
| Convenciones físicas y orden de migraciones | `docs/04-database/convenciones-fisicas-y-orden-migraciones.md` | Aprobado |
| Riesgos del modelo físico | `docs/04-database/riesgos-modelo-fisico.md` | Aprobado |
| Decisiones de infraestructura v1.8 | `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md` | Aprobado |
| Preparación técnica backend y PostgreSQL | `docs/06-architecture/preparacion-tecnica-backend-postgresql.md` | Aprobado |
| Checklist de inicialización segura 3B.1 | `docs/06-architecture/checklist-inicializacion-segura-3b1.md` | Aprobado |
| Estrategia de pruebas PostgreSQL | `docs/06-architecture/estrategia-pruebas-postgresql.md` | Aprobado |
| Decisiones de inicialización del backend v1.9 | `docs/07-decisions/CREDIMEX_Decisiones_Inicializacion_Backend_v1.9.md` | Aprobado |
| Cierre Fase 3B.1 — Inicialización del backend | `docs/06-architecture/cierre-fase-3b1-inicializacion-backend.md` | Aprobado |
| Decisiones de configuración PostgreSQL v2.0 | `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md` | Aprobado |
| Cierre Fase 3B.2 — Configuración PostgreSQL | `docs/06-architecture/cierre-fase-3b2-configuracion-postgresql.md` | Aprobado |
| Decisiones de guarda de pruebas PostgreSQL v2.1 | `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md` | Aprobado |
| Cierre subfase 3B.3.0A — Guarda PostgreSQL fail-closed | `docs/06-architecture/cierre-subfase-3b3-0a-guarda-postgresql-fail-closed.md` | Aprobado |
| Decisiones de helper de privilegios PostgreSQL v2.2 | `docs/07-decisions/CREDIMEX_Decisiones_Helper_Privilegios_PostgreSQL_v2.2.md` | Aprobado |
| Cierre subfase 3B.3.0B — Helper de privilegios PostgreSQL | `docs/06-architecture/cierre-subfase-3b3-0b-helper-privilegios-postgresql.md` | Aprobado |
| Decisiones del harness de migraciones PostgreSQL v2.3 | `docs/07-decisions/CREDIMEX_Decisiones_Harness_Migraciones_PostgreSQL_v2.3.md` | Aprobado |
| Cierre subfase 3B.3.1A — Harness owner-aware PostgreSQL | `docs/06-architecture/cierre-subfase-3b3-1a-harness-owner-aware-postgresql.md` | Aprobado |
| Extensión del harness y base RBAC v2.4 | `docs/07-decisions/CREDIMEX_Decisiones_Extension_Harness_y_RBAC_v2.4.md` | Aprobado |
| Cierre subfase 3B.3.1A.2 — Extensión multiarchivo y RBAC | `docs/06-architecture/cierre-subfase-3b3-1a2-extension-multifile-rbac.md` | Aprobado |
| Migraciones RBAC iniciales v2.5 | `docs/07-decisions/CREDIMEX_Decisiones_Migraciones_RBAC_v2.5.md` | Aprobado |
| Cierre subfase 3B.3.1B — Migraciones RBAC | `docs/06-architecture/cierre-subfase-3b3-1b-migraciones-rbac.md` | Aprobado |

La versión documental **v2.5** (subfase 3B.3.1B) incluye:

- migraciones de `roles`, `permisos` y `rol_permisos` en el repositorio;
- identity `GENERATED ALWAYS`, timestamps sin default y FK `NO ACTION`;
- CHECK regex estáticos mediante `pgsql_owner`;
- grants app únicamente `SELECT`, sin `USAGE`;
- ampliación read-only de `OwnerAwareMigrationInspection`;
- integración D-120 con `runFiles()` y dos corridas sin residuos;
- suite validada: 107 pruebas / 912 assertions;
- integración RBAC ejecutada dos veces: 1 prueba / 309 assertions
  por corrida;
- commit técnico `c790e8d`; commit documental `214d1ba` —
  `docs: documentar el cierre de la subfase 3B.3.1B`;
- siguiente subfase: 3B.3.1C.

La versión documental **v2.4** (subfase 3B.3.1A.2) incluye:

- `runFiles()` ordenado con máximo de ocho archivos y lifecycle único;
- rollback lógico inverso acotado y rollback exterior obligatorio;
- snapshots de `migrations` sin alterar registros ajenos;
- fixtures dependientes con dos FK y ejecución serial;
- D-121 aprobada y refinada a DDL/datos iniciales RBAC;
- timestamps RBAC sin default y FK `NO ACTION`;
- `migrationBatches()` protegido por la frontera sanitizada de
  precondiciones;
- rollback multi-file auditado como seguro;
- suite validada: 106 pruebas / 603 assertions;
- integración owner-aware ejecutada dos veces: 2 pruebas /
  75 assertions por corrida;
- commit técnico `9621ae1`; commit documental `0848014`;
- siguiente subfase: 3B.3.1B.

La versión documental **v2.3** (subfase 3B.3.1A) incluye:

- decisión D-120 aprobada;
- harness owner-aware con `Migrator` real y archivo único;
- transacción exterior `pgsql_owner` con rollback obligatorio y sin
  commit exterior;
- orden fail-closed con D-117 antes de path y precondiciones;
- factory `PostgreSqlGrantManager::fromOwnerMigration(app())`;
- allowlist de paths y pruebas serial only;
- suite validada: 91 pruebas / 319 assertions;
- smoke ejecutado dos veces sin residuos;
- Fase 3B.3 en curso; siguiente subfase: 3B.3.1B — migraciones de
  roles, permisos y rol_permisos.

La versión documental **v2.2** (subfase 3B.3.0B) incluye:

- decisiones D-118 y D-119;
- helper `PostgreSqlGrantManager` fail-closed;
- resolución de `DB_APP_ROLE` vía `config/credimex.php`;
- privilegios explícitos de tabla/secuencia sin `ALTER DEFAULT PRIVILEGES`;
- protección de `migrations` y `migrations_id_seq`;
- suite validada: 66 pruebas / 193 assertions;
- Fase 3B.3 en curso; siguiente subfase: 3B.3.1 — migraciones de roles,
  permisos y rol_permisos.

La versión documental **v2.1** (subfase 3B.3.0A) incluye:

- decisión D-117;
- guarda PHPUnit fail-closed implementada e integrada en
  `Tests\TestCase::setUpTraits()`;
- rechazo de `RefreshDatabase`, `DatabaseMigrations` y
  `DatabaseTruncation` hasta un mecanismo propio con `pgsql_owner`;
- validación de entorno, conexiones, usuarios, base, esquema y
  `search_path`;
- errores de inspección sanitizados;
- suite validada: 19 pruebas / 58 assertions;
- Fase 3B.3 en curso; siguiente subfase: 3B.3.0B (grants).

La versión documental **v2.0** (Fase 3B.2) incluye:

- decisiones D-108 a D-116;
- esquema dedicado `credimex` y `search_path` exclusivo;
- cuatro roles separados por entorno sin acceso cruzado;
- conexiones `pgsql` y `pgsql_owner`;
- bases UTF8 / ICU `es-MX` / UTC;
- tabla técnica `migrations` protegida (fuera de las 67);
- privilegios explícitos por clasificación de tabla;
- estrategia fail-closed documentada (aún no implementada en código);
- siguiente fase autorizada: 3B.3.

La versión documental **v1.9** (Fase 3B.1) incluye:

- decisiones D-103 a D-107;
- cierre técnico de la inicialización segura del backend;
- versión efectiva `laravel/framework` 13.22.0;
- backend exclusivamente API;
- `routes/api.php` canónico y vacío;
- eliminación de `User` y del frontend de demostración;
- política de `APP_KEY` y archivos locales;
- checklist 3B.1 marcado como completado;
- siguiente fase autorizada: 3B.2.

La versión documental **v1.8** (Fase 3B.0) incluye:

- decisiones D-92 a D-102;
- preparación técnica del backend y PostgreSQL;
- checklist de inicialización segura;
- estrategia de pruebas PostgreSQL;
- entorno validado (PHP 8.5.1, Composer 2.10.2, PostgreSQL 18.4, Git 2.50);
- `backend/` como ubicación canónica;
- control de migraciones predeterminadas;
- tabla técnica `migrations` aceptada (fuera de las 67 del dominio);
- drivers iniciales sin tablas técnicas;
- separación de roles PostgreSQL (`credimex_owner` / `credimex_app`);
- identity vía Schema Builder (sin BIGSERIAL);
- autenticación pospuesta a prueba de Sanctum;
- pruebas exclusivas contra PostgreSQL (sin SQLite).

La versión documental **v1.7** (Fase 3A.3) incluye:

- decisiones D-69 a D-91;
- modelo físico preliminar PostgreSQL;
- 67 tablas lógicas conservadas;
- 17 tablas con `id_publico`;
- tipos físicos;
- concurrencia;
- cifrado;
- retención;
- orden **futuro** de migraciones (las migraciones **no** están creadas).

## Documentos en construcción

| Entregable | Ruta prevista | Estado |
|---|---|---|
| Arquitectura técnica detallada | `docs/06-architecture/` | En construcción |
| Contrato OpenAPI inicial | `docs/05-api/` | En construcción |
| ADR adicionales | `docs/07-decisions/` | En construcción |

## Reglas de uso de la documentación

- `docs/00-index.md` es la única fuente de rutas. No se deben inventar carpetas ni renombrar las existentes.
- Todo documento nuevo se registra en las tablas de este índice antes de considerarse oficial.
- El documento maestro no se modifica dentro de esta normalización documental.
- El código backend vive en `backend/`; `api/` no debe recibir código (D-93).
- Las decisiones D-01 a D-20 están en `CREDIMEX_Decisiones_Resueltas_v1.3.md`.
- Las decisiones D-21 a D-32 están en `CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`.
- Las decisiones D-33 a D-53 están en `CREDIMEX_Decisiones_Modelo_Datos_v1.5.md`.
- Las decisiones D-54 a D-68 están en `CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`.
- Las decisiones D-69 a D-91 están en `CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`.
- Las decisiones D-92 a D-102 están en `CREDIMEX_Decisiones_Infraestructura_v1.8.md`.
- Las decisiones D-103 a D-107 están en `CREDIMEX_Decisiones_Inicializacion_Backend_v1.9.md`.
- Las decisiones D-108 a D-116 están en `CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`.
- La decisión D-117 está en `CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`.
- Las decisiones D-118 y D-119 están en `CREDIMEX_Decisiones_Helper_Privilegios_PostgreSQL_v2.2.md`.
- La decisión D-120 está en `CREDIMEX_Decisiones_Harness_Migraciones_PostgreSQL_v2.3.md`.
- La extensión de D-120 y la decisión D-121 refinada están en `CREDIMEX_Decisiones_Extension_Harness_y_RBAC_v2.4.md`.
- La implementación efectiva de D-121 / 3B.3.1B está en `CREDIMEX_Decisiones_Migraciones_RBAC_v2.5.md`.
