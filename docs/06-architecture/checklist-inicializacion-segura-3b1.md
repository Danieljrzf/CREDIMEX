# CREDIMEX — Checklist de inicialización segura (3B.1)

**Estado:** Completada (Fase 3B.1)
**Decisiones:** D-94, D-95, D-96, D-101, D-102, D-103 a D-107.
**Alcance:** Secuencia operativa. Sin SQL ni migraciones de dominio.

---

## Secuencia original

| # | Paso | Decisión | Verificación |
|---|---|---|---|
| 1 | Crear `backend/` con `composer create-project laravel/laravel backend "^13.0" --remove-vcs --no-scripts` | D-94 | El comando termina sin errores |
| 2 | Verificar ausencia de `backend/.git` | D-94 | No existe el directorio |
| 3 | Eliminar `database/migrations/0001_01_01_000000_create_users_table.php` | D-95 | Archivo no existe |
| 4 | Eliminar `database/migrations/0001_01_01_000001_create_cache_table.php` | D-95 | Archivo no existe |
| 5 | Eliminar `database/migrations/0001_01_01_000002_create_jobs_table.php` | D-95 | Archivo no existe |
| 6 | Eliminar `database/database.sqlite` si existe | D-95 | Archivo no existe |
| 7 | Ajustar `.gitignore` del repositorio raíz (incluir `backend/.env`, `backend/.env.testing`, `backend/vendor/`, `backend/storage/*.key`) | D-101 | Entradas presentes en `.gitignore` |
| 8 | Preparar `backend/.env.example` sin secretos | D-101 | Archivo existe; no contiene contraseñas reales |
| 9 | Copiar `.env.example` a `.env` | D-101 | Archivo `.env` existe |
| 10 | Configurar `.env` local con drivers aprobados y conexión PostgreSQL | D-96, D-97 | Variables de D-96 presentes; `DB_CONNECTION=pgsql` |
| 11 | Generar `APP_KEY` con `php artisan key:generate` | D-102 | `APP_KEY` tiene valor en `.env` |
| 12 | Corregir `phpunit.xml`: retirar `DB_CONNECTION=sqlite` y `DB_DATABASE=:memory:` | D-100 | No aparecen referencias a SQLite |
| 13 | Preparar `backend/.env.testing.example` sin secretos | D-101 | Archivo existe |
| 14 | Retirar scripts `setup` y `dev` de `composer.json` | D-102 | Scripts ausentes |
| 15 | Ejecutar `composer dump-autoload` | D-102 | Sin errores |
| 16 | Ejecutar `php artisan --version` | D-102 | Muestra Laravel 13.x |
| 17 | Ejecutar `php artisan about` | D-102 | Muestra configuración correcta |
| 18 | Confirmar que `database/migrations/` está vacío | D-95 | Sin archivos |
| 19 | Confirmar que no se ejecutó `migrate` | D-95 | No existen tablas en `credimex_dev` |

---

## Estado de cierre

### Completados

| Ítem | Estado |
|---|---|
| Rama de trabajo creada | Completado |
| Laravel creado con `--remove-vcs --no-scripts` | Completado |
| Ausencia de `.git` anidado | Completado |
| Eliminación de migraciones predeterminadas | Completado |
| Eliminación de `database.sqlite` | Completado |
| Configuración de PostgreSQL como conexión predeterminada | Completado |
| Drivers D-96 | Completado |
| Archivos `.env` protegidos | Completado |
| Retirada de SQLite en `phpunit.xml` | Completado |
| Eliminación de scripts `setup`, `dev` y `post-create-project-cmd` | Completado |
| `APP_KEY` local generada | Completado |
| `composer validate` | Completado |
| `composer audit` | Completado |
| `composer dump-autoload` | Completado |
| Laravel `--version` | Completado |
| Laravel `about` | Completado |
| Depuración del frontend | Completado |
| `routes/api.php` creado | Completado |
| `migrations/` vacío | Completado |
| Working tree limpio | Completado |

### Pendientes

| Ítem | Fase |
|---|---|
| Creación de bases y roles PostgreSQL | 3B.2 |
| Conexión real a PostgreSQL | 3B.2 |
| Creación de la tabla técnica `migrations` | 3B.2 |
| Pruebas contra `credimex_test` | 3B.2 |
| Migraciones del dominio | 3B.3+ |
| Prueba de Sanctum | 3B.3 |

---

## Qué no se hace en 3B.1

- No ejecutar `migrate`.
- No crear migraciones.
- No instalar Sanctum.
- No instalar Node ni NPM.
- No crear bases o roles PostgreSQL (eso es 3B.2).
- No crear controladores, modelos ni servicios.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Inicializacion_Backend_v1.9.md`
- `docs/06-architecture/preparacion-tecnica-backend-postgresql.md`
- `docs/06-architecture/cierre-fase-3b1-inicializacion-backend.md`
