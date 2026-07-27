# CREDIMEX — Cierre de la Fase 3B.2

**Estado:** Completada técnicamente (pendiente revisión y commit documental)
**Fecha:** 27 de julio de 2026
**Versión documental:** v2.0
**Decisiones:** D-108 a D-116.
**Alcance:** Bases, roles, esquema `credimex`, conexiones Laravel y
tabla técnica `migrations`. Sin migraciones ni tablas del dominio.

---

## 1. Resultado

La Fase 3B.2 quedó técnicamente validada.

PostgreSQL 18.4 local aloja `credimex_dev` y `credimex_test` con
esquema `credimex`, cuatro roles separados por entorno, aislamiento
`CONNECT`, `search_path=credimex` y timezone UTC.

Laravel dispone de `pgsql` (aplicación) y `pgsql_owner` (DDL /
migraciones). Las cuatro combinaciones de conexión fueron comprobadas
desde Tinker.

Se instaló la tabla técnica `migrations` en ambos entornos mediante
`migrate:install --database=pgsql_owner` (en testing con
`--env=testing`).

Todavía **no** existen migraciones ni tablas del dominio.
Todavía **no** está implementada la guarda fail-closed de PHPUnit.
Todavía **no** hay privilegios DML del dominio.

---

## 2. PostgreSQL validado

| Aspecto | Valor |
|---|---|
| Versión | PostgreSQL 18.4 |
| Host | `127.0.0.1:5432` |
| Encoding | UTF8 |
| Locale | ICU `es-MX` |
| Timezone | UTC |
| Autenticación | SCRAM-SHA-256 |

### Bases

| Base | Propietario | Roles con CONNECT |
|---|---|---|
| `credimex_dev` | `postgres` | `credimex_owner`, `credimex_app` |
| `credimex_test` | `postgres` | `credimex_test_owner`, `credimex_test_app` |

`PUBLIC` sin privilegios generales sobre las bases. Sin acceso cruzado
entre entornos.

### Esquemas

| Base | Esquema | Propietario |
|---|---|---|
| `credimex_dev` | `credimex` | `credimex_owner` |
| `credimex_test` | `credimex` | `credimex_test_owner` |

- `public` permanece presente; `PUBLIC` sin `CREATE` en `public`.
- Roles app: `USAGE` en `credimex`, sin `CREATE`.
- `search_path` por rol y base: exclusivamente `credimex`.

### Roles

| Rol | Entorno | Uso |
|---|---|---|
| `credimex_owner` | Desarrollo | Migraciones y DDL |
| `credimex_app` | Desarrollo | Operación de la aplicación |
| `credimex_test_owner` | Testing | Migraciones y DDL |
| `credimex_test_app` | Testing | Ejecución de pruebas |

Atributos: `LOGIN`, `NOSUPERUSER`, `NOCREATEDB`, `NOCREATEROLE`,
`INHERIT`, `NOREPLICATION`, `NOBYPASSRLS`.

Owners pueden crear/eliminar objetos en su esquema. Apps no tienen
`CREATE` en `credimex` ni en `public`.

---

## 3. Laravel validado

| Aspecto | Valor |
|---|---|
| Default | `pgsql` |
| App | variables `DB_*` |
| Owner | variables `DB_OWNER_*` |
| Desarrollo | `credimex_dev` |
| Testing | `credimex_test` |
| `search_path` | `credimex` |
| `sslmode` | `prefer` (solo local) |

Nota operativa: `php artisan db` no funciona de forma interactiva en
Windows por la limitación TTY de Symfony; no es una falla de
PostgreSQL. Las conexiones se validaron mediante Tinker.

---

## 4. Tabla técnica `migrations`

En cada esquema `credimex` (dev y test):

| Objeto | Tipo |
|---|---|
| `migrations` | tabla |
| `migrations_id_seq` | secuencia |
| `migrations_pkey` | índice |

Propietarios: `credimex_owner` (dev) y `credimex_test_owner` (test).

Roles app **sin** `SELECT`, `INSERT`, `UPDATE`, `DELETE` sobre
`migrations` y **sin** `USAGE` sobre `migrations_id_seq`.

Fuera del conteo de 67 tablas del dominio.

---

## 5. Incidencia controlada

Durante la configuración se intentó cambiar de base con `\c` sin
proporcionar contraseña; la sesión permaneció en la base administrativa
`postgres` y se creó allí un esquema `credimex` vacío.

Se confirmó que estaba vacío (sin tablas, secuencias ni otros objetos),
se eliminó **sin** `CASCADE` y se crearon los esquemas correctos en
`credimex_dev` y `credimex_test`.

Corrección local controlada, sin impacto en datos ni dominio.

---

## 6. Pendientes

Para la **Fase 3B.3** (definida previamente: migraciones de seguridad y
catálogos; prueba de Sanctum):

- migraciones del dominio (grupos iniciales);
- privilegios explícitos por tabla y secuencia (D-116), sin
  `ALTER DEFAULT PRIVILEGES` hacia roles app y sin grants cruzados
  entre desarrollo y testing; el mecanismo de resolución de roles
  por entorno queda por aprobar;
- prueba técnica de Sanctum frente a `sesiones_token`;
- implementación futura de la guarda fail-closed en PHPUnit (D-113).

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`
- `docs/06-architecture/cierre-fase-3b1-inicializacion-backend.md`
- `docs/06-architecture/estrategia-pruebas-postgresql.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
