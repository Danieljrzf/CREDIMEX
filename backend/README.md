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

## Estado actual

Fase 3B.1 — Inicialización del backend.

Todavía **no** se debe:

- ejecutar `php artisan migrate`;
- usar SQLite;
- instalar Sanctum;
- implementar autenticación;
- instalar Node ni ejecutar NPM;
- crear bases, roles, migraciones de dominio ni endpoints.

Los comandos de instalación de bases de datos y el resto de la
configuración operativa se documentarán en fases posteriores.
