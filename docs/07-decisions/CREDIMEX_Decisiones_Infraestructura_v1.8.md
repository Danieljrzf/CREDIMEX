# CREDIMEX — Decisiones de Infraestructura

**Versión:** 1.8
**Fecha:** 27 de julio de 2026
**Estado:** Aprobado
**Alcance:** Fase 3B.0 — Preparación técnica del backend y PostgreSQL.
Sin código, sin migraciones, sin SQL ejecutable.
**Relación:** Complementa D-01 a D-91. No las reemplaza ni modifica.

---

## Propósito

Documentar las decisiones D-92 a D-102 que fijan la plataforma,
ubicación del backend, inicialización segura de Laravel, control de
migraciones predeterminadas, drivers, roles PostgreSQL, estrategia de
identidad, autenticación pospuesta, pruebas exclusivas PostgreSQL,
manejo de entornos y secretos, y scripts de Composer.

Entregables asociados:

- `docs/06-architecture/preparacion-tecnica-backend-postgresql.md`
- `docs/06-architecture/checklist-inicializacion-segura-3b1.md`
- `docs/06-architecture/estrategia-pruebas-postgresql.md`

---

## D-92 — Plataforma

**Decisión:** Familias objetivo:

- Laravel ^13.0;
- PHP 8.5.x;
- Composer 2.x;
- PostgreSQL 18.x.

Entorno local actualmente validado:

- PHP 8.5.1;
- Composer 2.10.2;
- PostgreSQL 18.4;
- Git 2.50;
- `pdo_pgsql` y `pgsql` habilitados.

Los parches pueden actualizarse dentro de la misma rama estable
después de validar compatibilidad.

**Motivo:** Fijar versiones estables conocidas para evitar
incompatibilidades durante el desarrollo inicial.

**Restricciones:** No cambiar de rama mayor sin decisión documentada.

---

## D-93 — Ubicación canónica

**Decisión:** La aplicación Laravel vive únicamente en `backend/`.

La carpeta `api/` no debe recibir código ni considerarse la aplicación
backend.

**Motivo:** Evitar ambigüedad sobre la ubicación del código del
servidor.

**Acción:** Actualizar `AGENTS.md` para dejar explícita esta regla.

---

## D-94 — Inicialización segura

**Decisión:** El comando aprobado para 3B.1 es:

```
composer create-project laravel/laravel backend "^13.0" --remove-vcs --no-scripts
```

`--remove-vcs` evita metadatos Git anidados.

`--no-scripts` evita:

- generación automática de `APP_KEY`;
- creación de `database/database.sqlite`;
- ejecución automática de migraciones.

**Motivo:** Control total sobre qué se ejecuta y en qué orden.

**Restricciones:** No ejecutar `composer create-project` sin estas
opciones.

---

## D-95 — Migraciones predeterminadas

**Decisión:** Eliminar antes del primer `migrate`:

- `database/migrations/0001_01_01_000000_create_users_table.php`;
- `database/migrations/0001_01_01_000001_create_cache_table.php`;
- `database/migrations/0001_01_01_000002_create_jobs_table.php`.

Eliminar `database/database.sqlite` si existiera.

No autorizar:

- `users`;
- `password_reset_tokens`;
- `sessions`;
- `cache`;
- `cache_locks`;
- `jobs`;
- `job_batches`;
- `failed_jobs`;
- `personal_access_tokens`.

Se acepta únicamente la tabla técnica `migrations`.

`migrations` no cuenta entre las 67 tablas del dominio.

**Motivo:** El modelo CREDIMEX define sus propias tablas de usuarios,
sesiones y autenticación. Las tablas predeterminadas de Laravel
contradicen D-69 (identity), D-84 (español), D-77 (sin `deleted_at`
generalizado) y el inventario de 67 tablas.

---

## D-96 — Drivers iniciales

**Decisión:**

- `CACHE_STORE=file`;
- `SESSION_DRIVER=array`;
- `QUEUE_CONNECTION=sync`;
- `QUEUE_FAILED_DRIVER=null`;
- `FILESYSTEM_DISK=local`;
- `MAIL_MAILER=log`;
- `LOG_CHANNEL=stack`;
- `LOG_STACK=single`.

No Redis. No tablas de caché, sesiones o colas.

**Motivo:** Evitar tablas técnicas no autorizadas mientras no se
documente su necesidad.

---

## D-97 — PostgreSQL

**Decisión:**

Bases:

- `credimex_dev`;
- `credimex_test`.

Roles:

- `credimex_owner`: propietario; ejecuta migraciones; puede crear y
  modificar objetos.
- `credimex_app`: conexión ordinaria; puede operar datos; no puede
  `CREATE`, `ALTER` o `DROP`.

Configuración:

- UTF8;
- ICU `es-MX`, sujeto a validación;
- UTC.

**Motivo:** Separar privilegios de esquema (migraciones) de los de
operación (aplicación). Codificación y zona consistentes con el modelo
aprobado (D-73).

**Restricciones:** No escribir los comandos SQL hasta 3B.2.

---

## D-98 — Identidad

**Decisión:** Las PK deben ser `BIGINT GENERATED ALWAYS AS IDENTITY`.

Laravel 13 utilizará conceptualmente:

```php
$table->bigInteger('id')
    ->autoIncrement()
    ->generatedAs()
    ->always();
```

No aceptar `BIGSERIAL` como sustituto.

La primera migración real verificará el tipo resultante directamente
en PostgreSQL.

**Motivo:** D-69 fija identity del servidor. `BIGSERIAL` es un alias
de secuencia separada, no identity estándar SQL.

---

## D-99 — Autenticación

**Decisión:** No instalar Sanctum en 3B.1.

Antes de implementar autenticación se probará la adaptación de Sanctum
a:

- `sesiones_token`;
- `usuario_id`;
- `dispositivo_id`;
- hash `BYTEA`;
- expiración;
- revocación;
- auditoría;
- sin `personal_access_tokens`.

Un guard propio solo se considerará si la incompatibilidad se demuestra
mediante una prueba técnica.

**Motivo:** Evitar compromisos prematuros. Si Sanctum se adapta bien,
se conserva el mantenimiento del ecosistema; si no, se justifica una
alternativa.

---

## D-100 — Pruebas PostgreSQL

**Decisión:** Prohibido usar SQLite para probar migraciones, relaciones
o integridad.

`phpunit.xml` no debe conservar:

- `DB_CONNECTION=sqlite`;
- `DB_DATABASE=:memory:`.

Las pruebas utilizarán `credimex_test` en PostgreSQL.

Validar directamente:

- identity;
- `TIMESTAMPTZ`;
- FK;
- CHECK;
- índices únicos parciales;
- rollback.

**Motivo:** SQLite no soporta identity, CHECK reales, índices parciales
ni `TIMESTAMPTZ`. Las pruebas contra SQLite darían falsa confianza.

---

## D-101 — Entornos, locales y secretos

**Decisión:** Laravel conservará UTC en `config/app.php`. No agregar
`APP_TIMEZONE` mientras `config/app.php` permanezca fijo en UTC.

Variables aprobadas:

- `APP_LOCALE=es`;
- `APP_FALLBACK_LOCALE=es`;
- `APP_FAKER_LOCALE=es_MX`.

Archivos de entorno:

- `.env` local, no versionado;
- `.env.example`, versionado y sin secretos;
- `.env.testing` local, no versionado;
- `.env.testing.example`, versionado y sin secretos.

Agregar `.env.testing` al `.gitignore`.

No escribir contraseñas reales en documentación, commits o archivos
example.

`APP_KEY` se generará manualmente después de preparar `.env` y antes
de usar funcionalidad cifrada.

**Motivo:** Separar configuración versionable de secretos locales.
UTC como zona de almacenamiento (D-73).

---

## D-102 — Scripts Composer

**Decisión:** Debido a `--no-scripts`, después de limpiar y configurar
el proyecto se ejecutará:

- `php artisan key:generate`;
- `composer dump-autoload`;
- `php artisan --version`;
- `php artisan about`.

No ejecutar `migrate`.

Eliminar de `composer.json` durante 3B.1:

- script `setup`;
- script `dev`.

Conservar:

- `test`;
- `post-autoload-dump`;
- `post-update-cmd`;
- `pre-package-uninstall`.

No instalar Node ni ejecutar NPM en esta fase.

**Motivo:** Eliminar scripts que asumen SQLite, migraciones
automáticas o herramientas frontend no requeridas.

---

## Relación con D-01 a D-91

Estas decisiones **no alteran** D-01 a D-91 ni el inventario de 67
tablas. Preparan la infraestructura para implementar el modelo aprobado.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
- `docs/04-database/inventario-tablas-logicas.md`
- `docs/04-database/convenciones-fisicas-y-orden-migraciones.md`
