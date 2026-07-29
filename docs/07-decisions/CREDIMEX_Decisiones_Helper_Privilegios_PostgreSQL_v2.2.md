# CREDIMEX — Decisiones de Helper de Privilegios PostgreSQL

**Versión:** 2.2
**Fecha:** 28 de julio de 2026
**Estado:** Aprobado
**Alcance:** Subfase 3B.3.0B — Configuración y helper seguro de
GRANT / REVOKE. No modifica D-01 a D-117 ni el inventario de 67 tablas.
**Relación:** Complementa D-116 y D-117; no cierra la Fase 3B.3.

---

## Objetivo

Fijar el mecanismo único y fail-closed para conceder o revocar
privilegios de aplicación sobre tablas y secuencias en el esquema
`credimex`, con resolución del rol receptor por entorno y sin SQL de
privilegios improvisado en migraciones.

## Contexto

La subfase 3B.3.0A dejó operativa la guarda PHPUnit fail-closed (D-117).
La subfase 3B.3.0B implementa el helper que materializa los privilegios
explícitos previstos en D-116, sin crear todavía tablas del dominio ni
ejecutar GRANT/REVOKE reales sobre objetos de negocio.

Commit técnico:

- `4cd8847` — `feat: implementar helper seguro de privilegios PostgreSQL`

Entregable asociado:

- `docs/06-architecture/cierre-subfase-3b3-0b-helper-privilegios-postgresql.md`

---

## D-118 — Helper seguro y explícito de privilegios PostgreSQL

**Decisión:**

- Todas las operaciones futuras `GRANT` / `REVOKE` deben pasar por
  `App\Infrastructure\Database\PostgreSqlGrantManager`.
- Las operaciones administrativas de privilegios se ejecutan únicamente
  mediante la conexión `pgsql_owner`.
- La conexión `pgsql` solo se inspecciona para validar el usuario app
  real; no ejecuta `GRANT` ni `REVOKE`.
- No se permite SQL de privilegios construido directamente en
  migraciones (ni cadenas improvisadas fuera del helper).
- API operativa aprobada para migraciones:
  - `assertSafeContext()`;
  - `grantTable(string $table, array $privileges)`;
  - `revokeTable(string $table, array $privileges)`;
  - `grantSequence(string $sequence, array $privileges)`;
  - `revokeSequence(string $sequence, array $privileges)`;
  - `fromApplication()`.
- El constructor público existe exclusivamente para inyección de
  dependencias y pruebas controladas. Las migraciones deben obtener
  el manager mediante `PostgreSqlGrantManager::fromApplication(app())`
  y no construirlo manualmente.
- Tablas solo aceptan: `SELECT`, `INSERT`, `UPDATE`, `DELETE`.
- Secuencias solo aceptan: `USAGE`.
- Quedan prohibidos `ALL` y cualquier otro privilegio (incluidos
  `TRUNCATE`, `REFERENCES`, `TRIGGER`, `CREATE`, `ALTER`, `DROP`).
- No se utiliza `ALTER DEFAULT PRIVILEGES`.
- Los nombres de objeto deben ser identificadores simples, en
  minúsculas, sin esquema cualificado, y cumplir `^[a-z][a-z0-9_]*$`.
- Esquema, objeto y rol se delimitan con `quote_ident` vía
  `pgsql_owner`; no se escapan comillas manualmente.
- Están protegidos y no pueden recibir privilegios app:
  - `migrations`;
  - `migrations_id_seq`.
- En migraciones futuras, `GRANT` y `REVOKE` deben ser simétricos entre
  `up` y `down`.
- Los errores públicos deben estar sanitizados: sin contraseña, DSN,
  host interno, SQL original, SQLSTATE ni mensaje del controlador.
- Cualquier fallo de validación o de frontera es fail-closed: no se
  construye ni ejecuta privilegios inseguros.

**Estado de implementación:** implementada en el commit técnico
`4cd8847`.

**Motivo:** Evitar privilegios prematuros o cruzados, centralizar least
privilege y alinear el DDL operativo con D-116 sin `DEFAULT PRIVILEGES`.

---

## D-119 — Resolución del rol app por entorno

**Decisión:**

- El rol receptor se obtiene exclusivamente desde
  `config('credimex.database.app_role')`.
- `env('DB_APP_ROLE')` solo se lee dentro de `config/credimex.php`.
- Entorno `local`:
  - base `credimex_dev`;
  - owner `credimex_owner`;
  - app `credimex_app`;
  - `DB_APP_ROLE=credimex_app`.
- Entorno `testing`:
  - base `credimex_test`;
  - owner `credimex_test_owner`;
  - app `credimex_test_app`;
  - `DB_APP_ROLE=credimex_test_app`.
- `production` todavía no está permitido.
- Cualquier entorno no registrado en el catálogo falla de forma
  explícita.
- Cualquier cruce dev/test (rol o base incorrectos para el entorno)
  se bloquea.
- El rol configurado debe existir en PostgreSQL, tener `LOGIN` y
  `CONNECT` sobre la base actual.
- El rol debe coincidir con `current_user` de la conexión `pgsql`.
- Ambas conexiones (`pgsql` y `pgsql_owner`) deben usar:
  - esquema `credimex`;
  - `search_path` exactamente `credimex`;
  - `current_schemas(false)::text` exactamente `{credimex}`.

**Estado de implementación:** implementada en el commit técnico
`4cd8847` (`config/credimex.php` + validaciones del manager).

**Motivo:** Impedir concesiones cruzadas entre desarrollo y testing y
hacer reproducible la identidad del receptor sin hardcodear roles en
migraciones.

---

## Límites

Esta versión documental **no**:

- crea tablas del dominio;
- otorga permisos reales sobre objetos de negocio;
- aprueba un trait owner-aware de pruebas;
- inicia migraciones de seguridad;
- instala Sanctum;
- altera el inventario de 67 tablas.

## Amenazas mitigadas

- SQL de privilegios improvisado en migraciones.
- Cruce de roles o bases entre desarrollo y testing.
- Concesión accidental a `migrations` / `migrations_id_seq`.
- Uso de `ALL` o privilegios peligrosos.
- Filtración de secretos o DSN en excepciones.
- Identificadores cualificados o no delimitados de forma segura.

## Resultados de pruebas

| Conjunto | Resultado |
|---|---|
| `PostgreSqlGrantManager` (unitarias) | 43 passed / 112 assertions |
| `PostgreSqlBooleanConverter` (unitarias) | 3 passed / 4 assertions |
| Integración de lectura del helper | 1 passed / 19 assertions |
| Suite completa | 66 passed / 193 assertions |

La integración fue solo de lectura. No se ejecutaron `GRANT`/`REVOKE`
reales ni DDL/DML de dominio.

## Archivos técnicos principales

- `backend/config/credimex.php`
- `backend/app/Infrastructure/Database/PostgreSqlGrantManager.php`
- `backend/app/Infrastructure/Database/LaravelPostgreSqlGrantContextInspector.php`
- `backend/app/Infrastructure/Database/LaravelPostgreSqlIdentifierQuoter.php`
- `backend/app/Infrastructure/Database/LaravelPostgreSqlGrantSqlExecutor.php`
- `backend/app/Infrastructure/Database/PostgreSqlBooleanConverter.php`
- `backend/tests/Unit/Infrastructure/Database/PostgreSqlGrantManagerTest.php`
- `backend/tests/Unit/Infrastructure/Database/PostgreSqlBooleanConverterTest.php`
- `backend/tests/Feature/Infrastructure/Database/PostgreSqlGrantManagerIntegrationTest.php`
- `backend/.env.example` / `backend/.env.testing.example` (`DB_APP_ROLE`)

## Decisiones pendientes (no aprobadas)

- **D-120 (candidata):** mecanismo owner-aware para recreación o
  rollback de pruebas.
- **D-121 (candidata):** migraciones iniciales de seguridad. Su
  implementación está dividida en:
  - **3B.3.1** — roles, permisos y `rol_permisos`;
  - **3B.3.2** — usuarios y dispositivos.
  D-121 **no** está aprobada todavía.
- **D-122 (candidata):** spike de Laravel Sanctum frente a
  `sesiones_token`.

## Siguiente subfase

**3B.3.1 — migraciones de roles, permisos y rol_permisos**,
sin cerrar todavía la Fase 3B.3 completa.

---

## Relación con D-01 a D-117

Estas decisiones **no alteran** D-01 a D-117 ni el inventario de 67
tablas. Implementan el mecanismo de privilegios explícitos de D-116
para la subfase 3B.3.0B.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`
- `docs/06-architecture/cierre-subfase-3b3-0b-helper-privilegios-postgresql.md`
- `docs/06-architecture/cierre-subfase-3b3-0a-guarda-postgresql-fail-closed.md`
