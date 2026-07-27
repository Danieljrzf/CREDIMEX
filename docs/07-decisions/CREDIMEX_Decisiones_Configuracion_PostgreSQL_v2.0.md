# CREDIMEX — Decisiones de Configuración PostgreSQL

**Versión:** 2.0
**Fecha:** 27 de julio de 2026
**Estado:** Aprobado
**Alcance:** Fase 3B.2 — Bases, roles, esquema y conexiones Laravel.
No modifica D-01 a D-107 ni el inventario de 67 tablas.
**Relación:** Complementa D-92 a D-107.

---

## Propósito

Documentar las decisiones D-108 a D-116 que fijan el esquema dedicado,
los cuatro roles por entorno, las conexiones `pgsql` / `pgsql_owner`,
el manejo de secretos SCRAM, la creación de bases UTF8/ICU/UTC, el
aislamiento de pruebas, la protección de `migrations`, el rollback
administrativo y los privilegios explícitos por clasificación de tabla.

Entregable asociado:

- `docs/06-architecture/cierre-fase-3b2-configuracion-postgresql.md`

---

## D-108 — Esquema dedicado `credimex`

**Decisión:**

- Las 67 tablas del dominio se alojarán en el esquema `credimex`.
- No se utilizarán prefijos de esquema en los nombres lógicos.
- Laravel resuelve las tablas mediante `search_path=credimex`.
- `public` no forma parte del `search_path`.
- `pg_catalog` se resuelve implícitamente por PostgreSQL.
- La tabla técnica `migrations` reside en `credimex`, pero no cuenta
  como tabla del dominio.

**Motivo:** Separar el dominio de `public`, aplicar least privilege y
conservar los nombres documentados sin prefijos.

---

## D-109 — Cuatro roles separados por entorno

**Decisión:**

Desarrollo:

- `credimex_owner`: migraciones y DDL.
- `credimex_app`: operación de la aplicación.

Testing:

- `credimex_test_owner`: migraciones y DDL.
- `credimex_test_app`: ejecución de pruebas.

Atributos comunes: `LOGIN`, `NOSUPERUSER`, `NOCREATEDB`,
`NOCREATEROLE`, `INHERIT`, `NOREPLICATION`, `NOBYPASSRLS`.

Sin acceso cruzado: los roles de desarrollo solo conectan a
`credimex_dev`; los de testing solo a `credimex_test`.

No utilizar `pg_read_all_data` ni `pg_write_all_data`.

**Motivo:** Aislar privilegios y entornos; evitar que pruebas
destructivas alcancen desarrollo.

---

## D-110 — Conexiones `pgsql` y `pgsql_owner`

**Decisión:**

- `pgsql` es la conexión ordinaria de la aplicación (`DB_*`).
- `pgsql_owner` es exclusiva para DDL y migraciones (`DB_OWNER_*`).
- Nunca ejecutar migraciones usando `pgsql`.
- Comando autorizado: `php artisan migrate --database=pgsql_owner`
  (y equivalentes con `--env=testing` cuando corresponda).

**Motivo:** Separar la identidad de ejecución ordinaria de la de
migración.

---

## D-111 — Secretos y SCRAM

**Decisión:**

- Contraseñas asignadas de manera interactiva (`\password`).
- Autenticación `SCRAM-SHA-256`.
- Secretos únicamente en `.env` / `.env.testing` locales ignorados
  por Git.
- Nunca registrar secretos en documentación, comandos, ejemplos,
  chats o commits.

**Motivo:** Evitar filtración de credenciales y alinear con la política
de secretos de D-101 / D-107.

---

## D-112 — UTF8, ICU `es-MX` y UTC

**Decisión:**

- Bases creadas desde `template0`.
- `ENCODING UTF8`.
- `LOCALE_PROVIDER icu` con `ICU_LOCALE es-MX` (validado).
- Timezone de base `UTC` en `credimex_dev` y `credimex_test`.
- Propiedad de las bases: administrador local `postgres`.

**Motivo:** Codificación y zona coherentes con el modelo físico (D-73)
y con el diagnóstico real de PostgreSQL 18.4.

---

## D-113 — Pruebas aisladas y fail-closed

**Decisión:**

- PHPUnit debe usar exclusivamente `credimex_test`.
- Las migraciones de testing se ejecutan como `credimex_test_owner`
  (`pgsql_owner` con entorno testing).
- Las pruebas operan como `credimex_test_app` (`pgsql`).
- Una futura guarda debe abortar si la base activa no es
  `credimex_test`.

**Estado de implementación:** la guarda fail-closed **aún no** está
implementada en código; queda como requisito obligatorio para la
suite cuando se autorice.

**Motivo:** Evitar destrucción accidental de datos de desarrollo.

---

## D-114 — Tabla `migrations` protegida

**Decisión:**

- Creada con `migrate:install --database=pgsql_owner`.
- Reside en el esquema `credimex`.
- Propiedad del owner de cada entorno.
- Los roles app no tienen `SELECT`, `INSERT`, `UPDATE`, `DELETE`
  sobre `migrations` ni `USAGE` sobre `migrations_id_seq`.
- Queda fuera de las 67 tablas del dominio.

**Motivo:** La tabla es metadato del framework, no dominio; la app
no debe manipularla.

---

## D-115 — Rollback administrativo local

**Decisión:**

- Separado de `php artisan migrate:rollback`.
- La eliminación de bases o roles solo mediante procedimiento
  administrativo local controlado.
- No se documenta ni ejecuta un rollback en este cierre.

**Motivo:** Evitar confundir deshacer migraciones con destruir
infraestructura PostgreSQL.

---

## D-116 — Privilegios explícitos por clasificación de tabla

**Decisión:**

- No configurar `ALTER DEFAULT PRIVILEGES` que conceda automáticamente
  privilegios sobre tablas a los roles app.
- No configurar `ALTER DEFAULT PRIVILEGES` que conceda automáticamente
  privilegios sobre secuencias a los roles app.
- Los grants serán explícitos por tabla y secuencia, según la
  clasificación física de cada tabla.
- `USAGE` sobre una secuencia solo cuando el rol app tenga `INSERT`
  en la tabla correspondiente.
- Tablas financieras inmutables: sin `DELETE` y sin `UPDATE` cuando
  aplique.
- Los nombres de roles receptores no se escribirán directamente dentro
  de migraciones reutilizables.
- Los roles se resolverán mediante configuración controlada por entorno
  o mediante un mecanismo técnico común que se apruebe.
- Una migración ejecutada en testing nunca debe conceder privilegios a
  roles de desarrollo y viceversa.
- `migrations` seguirá sin permisos para los roles app.

**Estado:** el mecanismo de resolución de roles por entorno **aún no**
está implementado; se definirá al autorizar las migraciones de dominio.

**Motivo:** Respetar append-only, inmutabilidad y least privilege;
evitar DML global prematuro y concesiones cruzadas entre entornos.

---

## Relación con D-01 a D-107

Estas decisiones **no alteran** D-01 a D-107 ni el inventario de 67
tablas. Cierran la configuración PostgreSQL y las conexiones Laravel
de la Fase 3B.2.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Inicializacion_Backend_v1.9.md`
- `docs/06-architecture/cierre-fase-3b2-configuracion-postgresql.md`
- `docs/04-database/inventario-tablas-logicas.md`
