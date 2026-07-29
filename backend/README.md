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

Uso conceptual (solo desde migraciones revisadas):

```php
$grants = PostgreSqlGrantManager::fromApplication(app());
$grants->grantTable('roles', ['SELECT', 'INSERT', 'UPDATE', 'DELETE']);
$grants->grantSequence('roles_id_seq', ['USAGE']);
// en down: revokeTable / revokeSequence simétricos
```

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
`DatabaseTruncation`. Todavía no existe un trait propio de migraciones.

Resultados confirmados de la subfase 3B.3.0B: **66 pruebas**,
**193 assertions**.

## Estado actual

Fase 3B.3 en curso.

- 3B.3.0A (guarda fail-closed): técnicamente completada.
- 3B.3.0B (helper de privilegios): técnicamente completada.
- Siguiente subfase: **3B.3.1 — migraciones de roles, permisos y
  rol_permisos**.
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
