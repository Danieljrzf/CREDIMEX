# CREDIMEX — Decisiones de migraciones RBAC v2.5

**Versión:** 2.5
**Fecha:** 16 de septiembre de 2026
**Estado:** Aprobado
**Alcance:** Implementación efectiva de D-121 / 3B.3.1B. No crea una
decisión D-xxx nueva ni modifica el alcance de D-121 o D-122.

**Commit técnico 3B.3.1B:** `c790e8d` —
`feat: implementar migraciones RBAC iniciales`

**Commit documental v2.5:** pendiente de registrar.

## 1. Propósito

Registrar que las tres migraciones RBAC iniciales existen en el
repositorio, fueron validadas mediante D-120 y no se desplegaron
persistentemente en desarrollo ni producción durante esta subfase.

D-120 continúa **aprobada** y ampliada con soporte multi-file.
D-121 continúa **aprobada** para RBAC. D-122 continúa **pendiente**.

## 2. Alcance implementado

3B.3.1B incorpora:

- DDL de `roles`, `permisos` y `rol_permisos`;
- constraints e índices físicos;
- grants `SELECT`;
- ampliación read-only de `OwnerAwareMigrationInspection`;
- integración serial mediante `runFiles()`;
- pruebas funcionales de CHECK, UNIQUE, FK, `NO ACTION` e identity.

No incluye datos iniciales, seeders, modelos, API, usuarios,
dispositivos, `sesiones_token`, Sanctum ni autenticación. Esos límites
permanecen fuera de D-121.

## 3. Archivos técnicos

- `backend/database/migrations/2026_09_16_000001_create_roles_table.php`;
- `backend/database/migrations/2026_09_16_000002_create_permisos_table.php`;
- `backend/database/migrations/2026_09_16_000003_create_rol_permisos_table.php`;
- `backend/tests/Feature/Database/Migrations/RbacMigrationsIntegrationTest.php`;
- `backend/tests/Support/Migrations/OwnerAwareMigrationInspection.php`.

## 4. Esquema RBAC implementado

### 4.1 `roles`

Archivo: `2026_09_16_000001_create_roles_table.php`.

- `id BIGINT GENERATED ALWAYS AS IDENTITY`;
- PK `roles_pkey`;
- `codigo VARCHAR(64) NOT NULL`;
- `nombre VARCHAR(120) NOT NULL`;
- `activo BOOLEAN NOT NULL DEFAULT TRUE`;
- `created_at TIMESTAMPTZ NOT NULL` sin default;
- `updated_at TIMESTAMPTZ NOT NULL` sin default;
- UNIQUE `uq_roles_codigo`;
- CHECK `chk_roles_codigo`;
- sin UUID ni `deleted_at`.

Regex conceptual: `^[a-z][a-z0-9_]*$`.

Expresión PostgreSQL conceptual:

```sql
CHECK ("codigo" ~ '^[a-z][a-z0-9_]*$')
```

### 4.2 `permisos`

Archivo: `2026_09_16_000002_create_permisos_table.php`.

- `id BIGINT GENERATED ALWAYS AS IDENTITY`;
- PK `permisos_pkey`;
- `codigo VARCHAR(96) NOT NULL`;
- `modulo VARCHAR(64) NOT NULL`;
- `descripcion TEXT NULL`;
- `activo BOOLEAN NOT NULL DEFAULT TRUE`;
- `created_at TIMESTAMPTZ NOT NULL` sin default;
- `updated_at TIMESTAMPTZ NOT NULL` sin default;
- UNIQUE `uq_permisos_codigo`;
- CHECK `chk_permisos_modulo`;
- CHECK `chk_permisos_codigo`;
- sin UUID ni `deleted_at`.

Regex de módulo: `^[a-z][a-z0-9_]*$`.

```sql
CHECK ("modulo" ~ '^[a-z][a-z0-9_]*$')
```

Regex de código: `^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$`.

```sql
CHECK ("codigo" ~ '^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$')
```

En `permisos.codigo`, `\.` representa un punto literal entre módulo y
acción.

### 4.3 `rol_permisos`

Archivo: `2026_09_16_000003_create_rol_permisos_table.php`.

- `id BIGINT GENERATED ALWAYS AS IDENTITY`;
- PK `rol_permisos_pkey`;
- `rol_id BIGINT NOT NULL`;
- `permiso_id BIGINT NOT NULL`;
- `created_at TIMESTAMPTZ NOT NULL` sin default;
- UNIQUE `uq_rol_permisos_rol_id_permiso_id`;
- FK `fk_rol_permisos_rol_id`: `rol_id -> roles.id`;
- FK `fk_rol_permisos_permiso_id`: `permiso_id -> permisos.id`;
- índice `idx_rol_permisos_permiso_id`;
- sin UUID, `activo`, `updated_at` ni `deleted_at`.

Ambas FK son no diferibles, utilizan `ON UPDATE NO ACTION` y
`ON DELETE NO ACTION`, y no emplean `CASCADE`. No existe un índice
individual redundante sobre `rol_id`; el UNIQUE compuesto ya cubre ese
prefijo.

Cada `down()` revoca `SELECT` y elimina exclusivamente su tabla.

## 5. Identity y timestamps

Las tres PK se crean con:

```php
$table->id()->generatedAs()->always();
```

La inspección PostgreSQL confirmó:

- `data_type = bigint`;
- `is_identity = YES`;
- `identity_generation = ALWAYS`.

La prueba funcional intenta insertar un `id` manual y usa
`identity_manual` como código válido, evitando que el rechazo pueda
atribuirse también a `chk_roles_codigo`. No se documenta
`identity.manual` como evidencia final.

Los timestamps son `TIMESTAMPTZ NOT NULL`, sin
`DEFAULT CURRENT_TIMESTAMP`, `now()` ni triggers. Los inserts de testing
proporcionan timestamps explícitos. La actualización de `updated_at`
corresponderá a la lógica futura.

## 6. CHECK específicos de PostgreSQL

Blueprint no ofrece una API general suficiente para CHECK regex
nombrados. Las migraciones agregan únicamente estos CHECK mediante SQL
PostgreSQL estático y controlado:

- sin input externo;
- ejecutado explícitamente mediante `pgsql_owner`;
- limitado al esquema `credimex`;
- sin manipular manualmente la conexión default.

No existe construcción dinámica de estas expresiones.

## 7. Privilegios

Cada migración utiliza:

```php
PostgreSqlGrantManager::fromOwnerMigration(app())
```

El rol app recibe únicamente `SELECT` sobre `roles`, `permisos` y
`rol_permisos`. Se comprobó la ausencia de `INSERT`, `UPDATE`, `DELETE`,
`TRUNCATE` y `USAGE` sobre las secuencias identity. No se concedieron
privilegios temporales para facilitar las pruebas.

La app continúa sin privilegios sobre `migrations` y
`migrations_id_seq`.

## 8. Inspección owner-aware

`OwnerAwareMigrationInspection` se amplió exclusivamente para testing
con métodos read-only:

- `columns()`;
- `constraintMetadata()`;
- `foreignKeyMetadata()`;
- `indexMetadata()`;
- `identitySequence()`.

Estos métodos inspeccionan metadatos PostgreSQL y no escriben en la
base. `identitySequence()` resuelve la secuencia mediante dependencias
reales del catálogo, no construyendo ciegamente su nombre.

La incorporación de `TRUNCATE` solo permite consultar si el rol app
posee ese privilegio. No amplía `PostgreSqlGrantManager` ni permite
concederlo.

## 9. Pruebas funcionales

La integración valida:

- valores válidos e inválidos de `roles.codigo`,
  `permisos.modulo` y `permisos.codigo`;
- rechazo de códigos duplicados y de la relación
  `(rol_id, permiso_id)` duplicada;
- rechazo de `rol_id` y `permiso_id` inexistentes;
- rechazo del borrado de un padre referenciado por `NO ACTION`;
- rechazo de un `id` manual bajo `GENERATED ALWAYS`.

Cada violación esperada se aísla mediante un savepoint/transacción
anidada. La transacción exterior permanece utilizable después de cada
rechazo.

## 10. D-120, rollback y residuos

La integración usa `OwnerAwareMigrationTestHarness::runFiles()`:

- up: `roles -> permisos -> rol_permisos`;
- rollback lógico: `rol_permisos -> permisos -> roles`;
- rollback exterior obligatorio.

Las corridas confirmaron:

- cero tablas RBAC residuales;
- cero filas residuales de estas migraciones;
- baseline histórico de `migrations` intacto;
- default connection restaurada;
- `transactionLevel('pgsql_owner') = 0`.

## 11. Evidencia final

- OwnerAwareMigrationTestHarness:
  **34 passed / 325 assertions**;
- PostgreSqlGrantManager:
  **47 passed / 122 assertions**;
- integración owner-aware:
  **2 passed / 75 assertions**;
- integración RBAC corrida 1:
  **1 passed / 309 assertions**;
- integración RBAC corrida 2:
  **1 passed / 309 assertions**;
- suite completa:
  **107 passed / 912 assertions**;
- PHP lint correcto en los cinco archivos técnicos;
- Pint aprobado;
- `git diff --check` limpio.

## 12. Persistencia, datos y límites

Las migraciones existen en el repositorio, pero esta subfase no las
desplegó persistentemente en desarrollo ni producción. D-120 creó y
revirtió las tablas dentro de escenarios controlados de testing.

`DatabaseSeeder.php` continúa sin datos RBAC. No se insertaron
persistentemente `cobrador`, `supervisor`, `administrador`, permisos ni
relaciones. Ese trabajo corresponde a 3B.3.1C.

El inventario permanece en **67 tablas lógicas**: las tres tablas RBAC
ya estaban incluidas. Los fixtures de testing no pertenecen al
inventario.

## 13. Estado resultante

- D-120: aprobada y ampliada con soporte multi-file;
- D-121: aprobada para RBAC; 3B.3.1B implementada;
- D-122: pendiente;
- 3B.3.1B: completada técnica y documentalmente;
- siguiente subfase: **3B.3.1C — datos iniciales RBAC**.

No se crea tag. No se afirma merge ni push.
