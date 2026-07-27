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
| `pgsql_owner` | Reservada para migraciones y DDL |

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

## Estado actual

Fase 3B.2 — Configuración PostgreSQL (técnicamente completada).

Todavía **no** se debe:

- ejecutar migraciones del dominio;
- usar SQLite;
- instalar Sanctum;
- implementar autenticación;
- instalar Node ni ejecutar NPM;
- crear endpoints de negocio.
