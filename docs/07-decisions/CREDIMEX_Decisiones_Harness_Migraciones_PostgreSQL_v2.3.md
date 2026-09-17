# CREDIMEX — Decisiones del Harness de Migraciones PostgreSQL

**Versión:** 2.3
**Fecha:** 23 de agosto de 2026
**Estado:** Aprobado
**Alcance:** Subfase 3B.3.1A — prueba y rollback controlado de una
migración PostgreSQL mediante owner. No crea migraciones ni tablas del
dominio y no altera el inventario de 67 tablas.
**Relación:** Complementa D-117, D-118 y D-119; no cierra la Fase 3B.3.

---

## Objetivo

Fijar un mecanismo owner-aware y fail-closed, exclusivo de testing, que
permita ejecutar una migración concreta con el `Migrator` real de
Laravel, inspeccionarla, ejecutar su rollback y garantizar que no queden
residuos persistentes.

D-120 no es un mecanismo de recreación general de base.

Commit técnico:

- `4dcdd59` — `test: implementar harness owner-aware de migraciones PostgreSQL`

Entregable asociado:

- `docs/06-architecture/cierre-subfase-3b3-1a-harness-owner-aware-postgresql.md`

---

## D-120 — Harness owner-aware fail-closed para prueba y rollback controlado de migraciones PostgreSQL

**Decisión:** D-120 queda **aprobada** en la versión documental v2.3.

El mecanismo canónico es:

```text
Tests\TestCase / D-117
        ↓
OwnerAwareMigrationTestHarness
        ↓
validación contexto fail-closed
        ↓
validación path
        ↓
validación precondiciones
        ↓
transactionLevel owner == 0
        ↓
BEGIN exterior pgsql_owner
        ↓
Migrator real / archivo único
        ↓
up()
        ↓
assertions
        ↓
rollback Migrator / down()
        ↓
verificaciones internas
        ↓
ROLLBACK exterior obligatorio
        ↓
verificaciones persistentes
```

El rollback exterior se ejecuta siempre, incluso en el camino exitoso.
No existe `COMMIT` exterior.

### Límites expresos

D-120:

- no utiliza `migrate:fresh`;
- no utiliza `db:wipe`;
- no utiliza `DROP SCHEMA`;
- no utiliza truncado general;
- no utiliza `RefreshDatabase`;
- no utiliza `DatabaseMigrations`;
- no utiliza `DatabaseTruncation`;
- no utiliza `DatabaseTransactions` como mecanismo del harness;
- no ejecuta Artisan `migrate` ni `rollback` desde PHPUnit;
- no ejecuta seeders;
- no recrea la base;
- no elimina objetos ajenos a la migración concreta bajo prueba.

---

## Orden fail-closed

El orden aprobado de `OwnerAwareMigrationTestHarness::runFile()` es:

1. `assertReady()`;
2. validar archivo y path;
3. validar nombres y precondiciones;
4. comprobar `transactionLevel`;
5. abrir la transacción owner;
6. ejecutar el `Migrator`;
7. ejecutar assertions;
8. ejecutar rollback del `Migrator`;
9. verificar ausencia interna de objeto y registro;
10. ejecutar cleanup y rollback exterior.

La guarda D-117 se ejecuta antes de cualquier validación secundaria.
Una prueba unitaria específica demuestra:

> contexto inseguro + path inválido → falla por contexto antes de path.

---

## Contexto permitido

El harness solo opera con:

- entorno `testing`;
- base `credimex_test`;
- default ordinaria `pgsql`;
- conexión owner `pgsql_owner`;
- usuario owner `credimex_test_owner`;
- usuario app `credimex_test_app`;
- esquema `credimex`;
- `search_path=credimex`;
- `current_schemas(false)={credimex}`.

No existe API para proporcionar database, schema, role, owner, host,
DSN o password arbitrarios.

---

## Ownership de la transacción

Reglas obligatorias:

- `transactionLevel('pgsql_owner')` debe ser exactamente `0` al entrar;
- un nivel distinto de `0` falla antes de cualquier DDL;
- el harness abre exactamente una transacción exterior propia;
- el harness solo revierte la transacción que creó;
- no hace rollback indiscriminado de transacciones ajenas;
- el nivel final debe coincidir exactamente con el inicial;
- en el escenario aprobado, nivel inicial y final son `0`.

Está prohibido usar un patrón como:

```php
while ($connection->transactionLevel() > 0) {
    $connection->rollBack();
}
```

si no puede demostrarse ownership de todos esos niveles.

Laravel y PostgreSQL pueden crear savepoints internos durante `up()` y
`down()`. Esos niveles quedan contenidos dentro de la transacción
exterior.

---

## Default connection y Laravel Migrator

La aplicación ordinaria conserva `pgsql` como default.

Laravel 13, mediante `Migrator::runMethod()`, cambia temporalmente la
default a `pgsql_owner` durante una migración owner. El harness captura
la default previa y debe restaurarla exactamente después de éxito o
excepción.

Resultado confirmado en testing:

```text
default connection final = pgsql
```

---

## Compatibilidad con PostgreSqlGrantManager

### Contexto ordinario

```php
PostgreSqlGrantManager::fromApplication(app())
```

Se utiliza cuando la aplicación tiene `pgsql` como default. Conserva
todas las validaciones de D-118 y D-119.

### Contexto de migración owner

```php
PostgreSqlGrantManager::fromOwnerMigration(app())
```

Esta factory explícita solo es válida durante una migración ejecutada
por owner. Debe:

1. verificar runtime default `pgsql_owner`;
2. verificar una transacción owner activa;
3. cambiar temporalmente la default a `pgsql`;
4. delegar la construcción y validaciones a `fromApplication()`;
5. restaurar exactamente `pgsql_owner` en `finally`.

No abre transacciones, no hace commit y no hace rollback. Los
`GRANT`/`REVOKE` continúan ejecutándose exclusivamente por
`pgsql_owner`.

La factory no acepta database, schema, role, owner, host, DSN ni
password como argumentos. El rol receptor continúa derivándose
exclusivamente de:

```text
config('credimex.database.app_role')
```

D-118 y D-119 no se relajan.

### Regla para migraciones futuras

Las migraciones CREDIMEX que necesiten administrar privilegios deben
obtener el helper mediante:

```php
$grants = PostgreSqlGrantManager::fromOwnerMigration(app());
```

No deben:

- manipular manualmente `DB::setDefaultConnection(...)`;
- implementar `try/finally` propios para alternar conexiones;
- escribir SQL directo de `GRANT` / `REVOKE`;
- construir manualmente el manager.

---

## Allowlist de paths

El harness acepta exclusivamente un archivo `.php` individual,
existente y resuelto de forma canónica bajo uno de estos roots:

- `backend/database/migrations/`;
- `backend/tests/Fixtures/migrations/`.

Rechaza:

- directorios;
- paths inexistentes;
- extensiones distintas de `.php`;
- traversal;
- paths externos;
- symlinks que escapen del root;
- ejecución automática de un directorio completo.

No existe API de migración masiva.

> **Extensión v2.4:** D-120 admite posteriormente `runFiles()` para
> escenarios controlados de uno a ocho archivos explícitos. Continúan
> prohibidos directorios, globs y ejecución masiva no acotada.

---

## Migrator y repositorio técnico

D-120 usa `Illuminate\Database\Migrations\Migrator`
programáticamente, no Artisan.

El `Migrator`:

- opera con `pgsql_owner`;
- ejecuta un único archivo concreto;
- usa el repositorio real `migrations`;
- registra temporalmente la migración durante `up`;
- elimina ese registro durante rollback;
- puede utilizar savepoints internos;
- queda contenido dentro de la transacción exterior.

El harness no crea ni elimina la tabla técnica y no utiliza
`deleteRepository()`. Tampoco modifica registros ajenos.

`migrations` y `migrations_id_seq` permanecen propiedad owner y sin
privilegios para el rol app. D-118 continúa bloqueando ambos objetos.

---

## Fixture smoke

Fixture aprobada:

`backend/tests/Fixtures/migrations/0000_00_00_000000_create_zz_test_owner_migration_harness_table.php`

Su tabla es exclusiva de testing y no forma parte del inventario de 67
tablas.

La fixture demuestra:

### `up`

- creación de su tabla;
- PK identity;
- schema y ownership correctos;
- `GRANT SELECT` exclusivo al rol app.

### `down`

- `REVOKE SELECT`;
- eliminación exclusiva de su propia tabla.

Usa `PostgreSqlGrantManager::fromOwnerMigration(app())`. No contiene
cambios manuales de default, SQL directo de privilegios, limpieza
global ni objetos de dominio.

---

## Inspección y grants dentro de la transacción

`OwnerAwareMigrationInspection` es infraestructura read-only para
inspeccionar:

- existencia de objetos;
- schema;
- owner;
- PK e identity;
- registro en `migrations`;
- privilegios con funciones y metadatos PostgreSQL.

No ejecuta DDL ni DML de negocio.

Los grants se verifican desde la misma conexión owner mientras el DDL
aún no tiene commit. No se intenta un `SELECT` real mediante
`credimex_test_app`, porque esa conexión usa otra sesión y no puede ver
el objeto no confirmado.

D-120 no introduce un modo committed. Una prueba comportamental real
desde app queda para una fase posterior si resulta necesaria.

---

## Concurrencia

Los tests owner-aware de migraciones son **SERIAL ONLY** porque
comparten:

- base `credimex_test`;
- schema `credimex`;
- nombres concretos de objetos;
- repositorio `migrations`.

No se habilita parallel testing para estos escenarios.

---

## Sanitización

Las excepciones son fail-closed y solo identifican la etapa lógica:

- contexto;
- path;
- up;
- assertions;
- down;
- cleanup.

No exponen password, DSN, host interno, SQL original, SQLSTATE, mensaje
completo del driver ni `previous` sensible.

---

## Archivos técnicos

- `backend/tests/Support/Migrations/OwnerAwareMigrationTestHarness.php`
- `backend/tests/Support/Migrations/OwnerAwareMigrationException.php`
- `backend/tests/Support/Migrations/OwnerAwareMigrationInspection.php`
- `backend/tests/Fixtures/migrations/0000_00_00_000000_create_zz_test_owner_migration_harness_table.php`
- `backend/tests/Unit/Support/Migrations/OwnerAwareMigrationTestHarnessTest.php`
- `backend/tests/Feature/Support/Migrations/OwnerAwareMigrationHarnessIntegrationTest.php`
- `backend/app/Infrastructure/Database/PostgreSqlGrantManager.php`
- `backend/tests/Unit/Infrastructure/Database/PostgreSqlGrantManagerTest.php`

---

## Evidencia técnica

| Conjunto | Resultado |
|---|---|
| Unitarias harness | 20 passed / 88 assertions |
| Unitarias `PostgreSqlGrantManager` | 47 passed / 122 assertions |
| Smoke integración — corrida 1 | 1 passed / 28 assertions |
| Smoke integración — corrida 2 | 1 passed / 28 assertions |
| Suite completa | 91 passed / 319 assertions |

Confirmaciones adicionales:

- `php -l`: 8 archivos sin errores;
- `git diff --check`: limpio;
- fixture ausente persistentemente tras ambas corridas;
- fila fixture ausente persistentemente en `migrations`;
- default restaurada a `pgsql`;
- `transactionLevel('pgsql_owner')` restaurado a `0`;
- app sin privilegios sobre `migrations`;
- app sin privilegios sobre `migrations_id_seq`.

---

## Estado de decisiones

- **D-118:** aprobada, sin cambios sustanciales.
- **D-119:** aprobada, sin cambios.
- **D-120:** **aprobada en v2.3**.
- **D-121:** candidata.
- **D-122:** candidata.

D-120 complementa D-117 y D-118. No reemplaza ni debilita decisiones
anteriores.

---

## Estado de 3B.3.1

- **3B.3.1.0 — Auditoría de migraciones default:** confirmada.
  `backend/database/migrations/` estaba vacío; las migraciones default
  Laravel ya se habían retirado. No requirió cambios ni commit propio.
- **3B.3.1A — Harness owner-aware:** completada técnica y
  documentalmente con v2.3.
- **Siguiente subfase:** **3B.3.1B — migraciones de roles, permisos y
  rol_permisos**.
- **Posterior:** **3B.3.1C — datos iniciales RBAC**.

Todavía no existen migraciones de dominio ni seeds RBAC.

El diseño previsto para 3B.3.1B conserva:

- tablas `roles`, `permisos` y `rol_permisos`;
- grants app iniciales `SELECT` en las tres tablas;
- sin `USAGE` de secuencias porque app no insertará registros;
- sin usuarios, dispositivos, `sesiones_token` ni Sanctum.

D-121 continúa candidata.

**Actualización v2.4:** las líneas anteriores conservan el estado
histórico de v2.3. D-121 fue refinada y aprobada posteriormente para
3B.3.1B (DDL de `roles`, `permisos` y `rol_permisos`) y 3B.3.1C (datos
iniciales RBAC). Usuarios, dispositivos, `sesiones_token`, Sanctum y
autenticación quedaron fuera de su alcance. D-122 continúa pendiente.

---

## Límites de v2.3

Esta versión:

- no crea migraciones ni tablas del dominio;
- no crea seeds RBAC;
- no instala Sanctum;
- no implementa autenticación;
- no aprueba D-121 ni D-122;
- no crea tag;
- no registra merge;
- no altera el inventario de 67 tablas.

La fixture es transaccional, no persiste y no cuenta dentro del
inventario.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Helper_Privilegios_PostgreSQL_v2.2.md`
- `docs/06-architecture/cierre-subfase-3b3-1a-harness-owner-aware-postgresql.md`
- `docs/06-architecture/cierre-subfase-3b3-0b-helper-privilegios-postgresql.md`
