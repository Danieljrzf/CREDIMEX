# CREDIMEX — Backend

API central de CREDIMEX.

## Stack

- Laravel 13
- PHP 8.5
- PostgreSQL 18

## Ubicación

Esta carpeta (`backend/`) es la ubicación canónica de la aplicación Laravel
y está destinada exclusivamente a la API. No contiene frontend Laravel y
no utiliza Node, Vite ni NPM en esta fase.

La carpeta `api/` del repositorio no debe recibir código del backend.

El punto futuro de rutas HTTP es `routes/api.php`.

## Conexiones de base de datos

| Conexión | Uso |
|---|---|
| `pgsql` | Conexión ordinaria de la aplicación |
| `pgsql_owner` | Migraciones, DDL y operaciones administrativas de privilegios |

Desarrollo y testing usan roles diferentes. El `search_path` es
exclusivamente `credimex` (sin `public`).

Nunca ejecutar migraciones con la conexión `pgsql`.

Comando de migraciones:

```text
php artisan migrate --database=pgsql_owner
```

En testing: añadir `--env=testing`.

La tabla técnica `migrations` ya existe en `credimex` (dev y test).
Todavía no se han creado migraciones del dominio.

## Rol de aplicación (`DB_APP_ROLE`)

Configuración en `config/credimex.php`:

```text
config('credimex.database.app_role')
```

| Entorno | Base | Owner | App / `DB_APP_ROLE` |
|---|---|---|---|
| `local` | `credimex_dev` | `credimex_owner` | `credimex_app` |
| `testing` | `credimex_test` | `credimex_test_owner` | `credimex_test_app` |

Los ejemplos están en `.env.example` y `.env.testing.example`.
Los `.env` reales locales deben incluir `DB_APP_ROLE` y no se versionan.

## Helper de privilegios

`App\Infrastructure\Database\PostgreSqlGrantManager` es el único camino
aprobado para `GRANT` / `REVOKE` futuros.

Contexto ordinario de aplicación (default `pgsql`):

```php
$grants = PostgreSqlGrantManager::fromApplication(app());
```

Contexto de migración ejecutada por owner:

```php
$grants = PostgreSqlGrantManager::fromOwnerMigration(app());
$grants->grantTable('roles', ['SELECT', 'INSERT', 'UPDATE', 'DELETE']);
$grants->grantSequence('roles_id_seq', ['USAGE']);
// en down: revokeTable / revokeSequence simétricos
```

Las migraciones no deben manipular manualmente la default connection ni
implementar `try/finally` propios para alternar `pgsql` y
`pgsql_owner`. La compatibilidad con el cambio temporal realizado por
Laravel Migrator está centralizada en `fromOwnerMigration()`.

Privilegios permitidos:

- tablas: `SELECT`, `INSERT`, `UPDATE`, `DELETE`;
- secuencias: `USAGE`.

Prohibidos: `ALL` y cualquier otro privilegio.
Protegidos: `migrations`, `migrations_id_seq`.
Sin `ALTER DEFAULT PRIVILEGES`.

Advertencia: `grantTable` / `revokeTable` / `grantSequence` /
`revokeSequence` solo deben usarse desde migraciones revisadas. No
llamarlos de forma improvisada desde código de aplicación ordinaria.

## Pruebas

Suite completa:

```text
php artisan test
```

Pruebas unitarias del manager:

```text
php artisan test --filter=PostgreSqlGrantManagerTest
```

Pruebas del convertidor booleano:

```text
php artisan test --filter=PostgreSqlBooleanConverterTest
```

Integración de lectura del helper:

```text
php artisan test --filter=PostgreSqlGrantManagerIntegrationTest
```

La suite valida automáticamente el entorno PostgreSQL de testing
mediante la guarda fail-closed en `Tests\TestCase`.

Están prohibidos `RefreshDatabase`, `DatabaseMigrations` y
`DatabaseTruncation`.

## Pruebas owner-aware de migraciones

`Tests\Support\Migrations\OwnerAwareMigrationTestHarness` es el
mecanismo aprobado por D-120 para probar migraciones PostgreSQL.

Características:

- exclusivamente entorno `testing` y base `credimex_test`;
- `Migrator` real programático;
- un archivo `.php` individual por escenario;
- conexión `pgsql_owner`;
- transacción exterior owner;
- rollback exterior obligatorio incluso en éxito;
- sin commit exterior;
- fixture smoke transaccional;
- tests **SERIAL ONLY**.

El harness no utiliza `DatabaseTransactions`, `migrate:fresh`,
`db:wipe`, `DROP SCHEMA` ni Artisan `migrate` / `rollback` desde
PHPUnit.

Fixture:

```text
tests/Fixtures/migrations/0000_00_00_000000_create_zz_test_owner_migration_harness_table.php
```

La fixture no pertenece al inventario de 67 tablas y no deja residuos.

Resultados confirmados al cierre de 3B.3.1A: **91 pruebas**,
**319 assertions**.

## Estado actual

Fase 3B.3 en curso.

- 3B.3.0A (guarda fail-closed): técnicamente completada.
- 3B.3.0B (helper de privilegios): técnicamente completada.
- 3B.3.1.0 (auditoría de migraciones default): confirmada, sin cambios.
- 3B.3.1A (harness owner-aware): completada técnica y documentalmente.
- Siguiente subfase: **3B.3.1B — migraciones de roles, permisos y
  rol_permisos**.
- Posterior: **3B.3.1C — datos iniciales RBAC**.
- Posterior: **3B.3.2** — usuarios y dispositivos.

Todavía **no** se debe:

- ejecutar migraciones del dominio;
- usar SQLite;
- instalar Sanctum;
- implementar autenticación;
- instalar Node ni ejecutar NPM;
- crear endpoints de negocio;
- usar los traits estándar de recreación de base;
- escribir `GRANT` / `REVOKE` fuera del helper.
