# CREDIMEX — Preparación técnica del backend y PostgreSQL

**Estado:** Aprobado (Fase 3B.0)
**Decisiones:** D-92 a D-102.
**Alcance:** Plan técnico. Sin código, sin SQL, sin migraciones.

---

## 1. Entorno validado

| Componente | Versión | Estado |
|---|---|---|
| PHP | 8.5.1 | Disponible |
| Composer | 2.10.2 | Disponible |
| PostgreSQL | 18.4 | Disponible |
| Git | 2.50 | Disponible |
| `pdo_pgsql` | habilitado | Disponible |
| `pgsql` | habilitado | Disponible |

Las versiones pertenecen a las familias objetivo (D-92). Los parches
pueden actualizarse dentro de la misma rama estable.

---

## 2. Ubicación del backend

La aplicación Laravel vive en `backend/` (D-93).

La carpeta `api/` existe en el repositorio pero **no** debe recibir
código de la aplicación backend.

---

## 3. Inicialización segura (D-94)

Comando aprobado:

```
composer create-project laravel/laravel backend "^13.0" --remove-vcs --no-scripts
```

Razones de las opciones:

| Opción | Propósito |
|---|---|
| `--remove-vcs` | Evitar repositorio Git anidado en `backend/` |
| `--no-scripts` | Evitar `APP_KEY` automática, `database.sqlite` y migraciones |

---

## 4. Control de migraciones predeterminadas (D-95)

Laravel 13 incluye 3 migraciones que crean 8 tablas no autorizadas:

| Migración | Tablas | Acción |
|---|---|---|
| `0001_01_01_000000_create_users_table.php` | `users`, `password_reset_tokens`, `sessions` | Eliminar |
| `0001_01_01_000001_create_cache_table.php` | `cache`, `cache_locks` | Eliminar |
| `0001_01_01_000002_create_jobs_table.php` | `jobs`, `job_batches`, `failed_jobs` | Eliminar |

También eliminar `database/database.sqlite` si existiera.

Tabla técnica aceptada: `migrations` (fuera de las 67 del dominio).

---

## 5. Drivers iniciales (D-96)

| Variable | Valor | Tabla requerida |
|---|---|---|
| `CACHE_STORE` | `file` | Ninguna |
| `SESSION_DRIVER` | `array` | Ninguna |
| `QUEUE_CONNECTION` | `sync` | Ninguna |
| `QUEUE_FAILED_DRIVER` | `null` | Ninguna |
| `FILESYSTEM_DISK` | `local` | Ninguna |
| `MAIL_MAILER` | `log` | Ninguna |
| `LOG_CHANNEL` | `stack` | Ninguna |
| `LOG_STACK` | `single` | Ninguna |

---

## 6. PostgreSQL (D-97)

### Bases de datos

| Base | Uso |
|---|---|
| `credimex_dev` | Desarrollo local |
| `credimex_test` | Pruebas automatizadas |

### Roles

| Rol | Privilegios |
|---|---|
| `credimex_owner` | Propietario; ejecuta migraciones; `CREATE`, `ALTER`, `DROP` |
| `credimex_app` | Operación; `SELECT`, `INSERT`, `UPDATE`, `DELETE`; `USAGE` en secuencias |

### Configuración

| Parámetro | Valor |
|---|---|
| Codificación | UTF8 |
| Proveedor | ICU |
| Locale | `es-MX` (sujeto a validación) |
| Zona horaria | UTC |

---

## 7. Identidad (D-98)

Las 67 tablas usarán `BIGINT GENERATED ALWAYS AS IDENTITY` como PK.

Forma conceptual en Laravel 13:

```php
$table->bigInteger('id')
    ->autoIncrement()
    ->generatedAs()
    ->always();
```

No se acepta `BIGSERIAL`. La primera migración real verificará el tipo
resultante directamente en PostgreSQL.

---

## 8. Autenticación (D-99)

No se instala Sanctum en 3B.1.

Se probará la adaptación de Sanctum a `sesiones_token` durante 3B.3.
Un guard propio solo se considerará si la prueba demuestra
incompatibilidad.

---

## 9. Entornos y secretos (D-101)

| Archivo | Versionado | Contenido |
|---|---|---|
| `.env` | No | Configuración local con secretos |
| `.env.example` | Sí | Plantilla sin secretos |
| `.env.testing` | No | Configuración de pruebas con secretos |
| `.env.testing.example` | Sí | Plantilla sin secretos |

UTC fijo en `config/app.php`. `APP_LOCALE=es`.
`APP_KEY` se genera manualmente después de preparar `.env`.

---

## 10. Scripts Composer (D-102)

Eliminar de `composer.json`:

- script `setup`;
- script `dev`.

Conservar: `test`, `post-autoload-dump`, `post-update-cmd`,
`pre-package-uninstall`.

Después de limpiar y configurar: `php artisan key:generate`,
`composer dump-autoload`, `php artisan --version`, `php artisan about`.

No instalar Node ni ejecutar NPM.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
- `docs/06-architecture/checklist-inicializacion-segura-3b1.md`
- `docs/06-architecture/estrategia-pruebas-postgresql.md`
