# CREDIMEX — Cierre de la subfase 3B.3.0B

**Estado:** Completada técnicamente (pendiente commit documental)
**Fecha:** 28 de julio de 2026
**Versión documental:** v2.2
**Decisiones:** D-118 y D-119.
**Alcance:** Configuración `DB_APP_ROLE` / `config/credimex.php` y
helper seguro de privilegios PostgreSQL. Sin migraciones ni tablas del
dominio. Sin GRANT/REVOKE reales sobre objetos de negocio. No cierra la
Fase 3B.3 completa.

---

## 1. Alcance implementado

La subfase **3B.3.0B** quedó técnicamente validada.

Se añadió:

- `config/credimex.php` con esquema, `app_role` y catálogo
  `local` / `testing`;
- `PostgreSqlGrantManager` y dependencias en
  `App\Infrastructure\Database`;
- pruebas unitarias e integración de solo lectura;
- `DB_APP_ROLE` en `.env.example` y `.env.testing.example`.

Todavía **no** existen migraciones ni tablas del dominio.
Todavía **no** se otorgaron permisos reales de aplicación sobre tablas
de negocio.
Todavía **no** existe trait owner-aware de pruebas (D-120).
Todavía **no** está instalado Sanctum (D-122).

La **Fase 3B.3** permanece en curso. La siguiente subfase autorizada es
**3B.3.1 — migraciones de roles, permisos y rol_permisos**.

---

## 2. Commit técnico

| Campo | Valor |
|---|---|
| Hash | `4cd8847` |
| Mensaje | `feat: implementar helper seguro de privilegios PostgreSQL` |
| Rama | `feat/migraciones-seguridad-catalogos` |

---

## 3. Arquitectura del helper

Componentes:

| Pieza | Rol |
|---|---|
| `PostgreSqlGrantManager` | API operativa aprobada para migraciones y orquestación fail-closed |
| `PostgreSqlGrantContextInspector` | Inspección de conexiones y rol |
| `PostgreSqlIdentifierQuoter` | `quote_ident` vía `pgsql_owner` |
| `PostgreSqlGrantSqlExecutor` | Ejecución de sentencias validadas |
| `PostgreSqlBooleanConverter` | Booleanos PostgreSQL explícitos |
| `config/credimex.php` | Esquema, `app_role`, catálogo de entornos |

## 4. Flujo fail-closed

Orden en operaciones de objeto:

1. Validar nombre de objeto (minúsculas, patrón, lista prohibida).
2. Normalizar y validar privilegios.
3. `assertSafeContext()` (configuración + inspección real).
4. `quote_ident` de esquema, objeto y rol.
5. Composición privada del SQL.
6. Ejecución solo por `pgsql_owner`.

Si falla cualquier paso previo a la ejecución, no se invoca el
executor. Fallos de inspector/quoter/executor se sanitizan en el
límite del manager.

## 5. Conexiones utilizadas

| Conexión | Uso en el helper |
|---|---|
| `pgsql_owner` | Inspección administrativa, `quote_ident`, GRANT/REVOKE |
| `pgsql` | Solo inspección del usuario app (`current_user`) |

Default debe ser `pgsql`. Drivers de ambas conexiones: `pgsql`.

## 6. API operativa aprobada para migraciones

- `fromApplication()`
- `assertSafeContext()`
- `grantTable()`
- `revokeTable()`
- `grantSequence()`
- `revokeSequence()`

El constructor público existe exclusivamente para inyección de
dependencias y pruebas controladas. Las migraciones deben obtener el
manager mediante `PostgreSqlGrantManager::fromApplication(app())` y no
construirlo manualmente.

No se exponen parámetros de composición SQL al llamador.

## 7. Privilegios permitidos

| Objeto | Privilegios |
|---|---|
| Tabla | `SELECT`, `INSERT`, `UPDATE`, `DELETE` |
| Secuencia | `USAGE` |

Prohibidos: `ALL` y cualquier otro privilegio. Sin
`ALTER DEFAULT PRIVILEGES`.

## 8. Protección de `migrations`

Rechazados antes de inspeccionar PostgreSQL:

- `migrations`
- `migrations_id_seq`

La aplicación debe continuar sin permisos sobre esos objetos.

## 9. `quote_ident`

Esquema fijo `credimex`, objeto y rol se delimitan con
`select quote_ident(?)` sobre `pgsql_owner`. No hay escapado manual.

## 10. Sanitización

Mensajes públicos controlados para fallos de frontera. Sin contraseña,
DSN, host interno, SQL original, SQLSTATE ni `previous` sensible.

## 11. Pruebas y resultados

| Conjunto | Resultado |
|---|---|
| Sintaxis PHP (17 archivos) | Sin errores |
| Unitarias `PostgreSqlGrantManager` | 43 passed / 112 assertions |
| Unitarias `PostgreSqlBooleanConverter` | 3 passed / 4 assertions |
| Integración de lectura | 1 passed / 19 assertions |
| Suite completa | 66 passed / 193 assertions |

## 12. Límites y confirmaciones

No se ejecutaron en esta subfase:

- `GRANT` / `REVOKE` reales sobre dominio;
- `CREATE TABLE` / `ALTER TABLE` / `DROP`;
- `INSERT` / `UPDATE` / `DELETE`;
- migraciones del dominio.

Criterio de cierre técnico cumplido: helper + configuración + pruebas
verdes sin alterar PostgreSQL de dominio.

## 13. Siguiente paso

**3B.3.1 — migraciones de roles, permisos y rol_permisos**.

La subfase posterior **3B.3.2** cubrirá usuarios y dispositivos.

Pendientes / candidatas: D-120, D-121, D-122.
D-121 sigue como candidata general; su implementación se divide en
3B.3.1 y 3B.3.2.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Helper_Privilegios_PostgreSQL_v2.2.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`
- `docs/06-architecture/cierre-subfase-3b3-0a-guarda-postgresql-fail-closed.md`
