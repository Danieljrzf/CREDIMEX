# CREDIMEX — Cierre de la subfase 3B.3.0A

**Estado:** Completada técnicamente (pendiente commit documental)
**Fecha:** 28 de julio de 2026
**Versión documental:** v2.1
**Decisión:** D-117.
**Alcance:** Guarda PHPUnit fail-closed para PostgreSQL. Sin grants,
sin `DB_APP_ROLE`, sin migraciones ni tablas del dominio, sin trait
propio de migraciones. No cierra la Fase 3B.3 completa.

---

## 1. Resultado

La subfase **3B.3.0A** quedó técnicamente validada.

Se implementó una guarda fail-closed que aborta la suite si el entorno
de testing no coincide exactamente con las constantes de seguridad
(`credimex_test`, roles `credimex_test_app` / `credimex_test_owner`,
esquema y `search_path` `credimex`).

La guarda se integra en `Tests\TestCase::setUpTraits()` antes de
`parent::setUpTraits()`, rechaza traits estándar de recreación de base
y sanitiza los fallos de inspección.

Todavía **no** existen:

- helper de grants;
- `DB_APP_ROLE` / `config/credimex.php`;
- migraciones ni tablas del dominio;
- trait propio de migraciones con `pgsql_owner`;
- Sanctum instalado.

La **Fase 3B.3** permanece en curso. La siguiente subfase autorizada es
**3B.3.0B** — configuración y helper seguro de grants.

---

## 2. Commit técnico

| Campo | Valor |
|---|---|
| Hash | `51527f7` |
| Mensaje | `test: implementar guarda PostgreSQL fail-closed` |
| Rama | `feat/migraciones-seguridad-catalogos` |

---

## 3. Archivos técnicos

- `backend/tests/TestCase.php`
- `backend/tests/Support/PostgreSqlConnectionContext.php`
- `backend/tests/Support/PostgreSqlContextInspector.php`
- `backend/tests/Support/LaravelPostgreSqlContextInspector.php`
- `backend/tests/Support/PostgreSqlTestSafetyException.php`
- `backend/tests/Support/PostgreSqlTestSafetyGuard.php`
- `backend/tests/Unit/Support/PostgreSqlTestSafetyGuardTest.php`
- `backend/tests/Feature/Support/PostgreSqlTestSafetyGuardIntegrationTest.php`

---

## 4. Comportamiento de la guarda

Orden en `Tests\TestCase::setUpTraits()`:

1. Inventario de traits (`traitsUsedByTest` o
   `class_uses_recursive`).
2. Rechazo de `RefreshDatabase`, `DatabaseMigrations` y
   `DatabaseTruncation`.
3. Ejecución de `PostgreSqlTestSafetyGuard`.
4. Solo si todo es correcto: `return parent::setUpTraits()`.

Validaciones fail-closed:

| Aspecto | Valor exigido |
|---|---|
| Entorno | `testing` |
| Default | `pgsql` |
| Drivers | `pgsql` en `pgsql` y `pgsql_owner` |
| Base (`pgsql` / `pgsql_owner`) | `credimex_test` |
| Usuario app | `credimex_test_app` |
| Usuario owner | `credimex_test_owner` |
| Esquema | `credimex` |
| `search_path` | exactamente `credimex` |
| `current_schemas(false)` | exactamente `{credimex}` |

Los valores esperados son constantes; no se derivan de `DB_DATABASE` ni
de `DB_USERNAME`.

Fallos de inspección: mensaje fijo sanitizado, sin contraseña, DSN,
host interno, SQL ni mensajes del controlador. Si la guarda falla, no
se llama al padre y no continúa el ciclo de traits.

---

## 5. Validaciones ejecutadas

| Verificación | Resultado |
|---|---|
| Sintaxis PHP (8 archivos) | Sin errores |
| Pruebas unitarias | 18 aprobadas / 48 assertions (sin PostgreSQL real) |
| Prueba de integración | 1 aprobada / 10 assertions (solo lectura) |
| Suite completa | 19 pruebas / 58 assertions / sin fallos |

No se crearon migraciones ni tablas del dominio.
No se modificó PostgreSQL.
No se usaron traits de recreación de base.
No se modificaron `.env` ni `.env.testing`.

---

## 6. Pendientes

- **3B.3.0B:** configuración y helper seguro de grants.
- Trait propio de migraciones / recreación con `pgsql_owner`
  (candidata).
- Migraciones de seguridad y catálogos (subfases posteriores de 3B.3).
- Prueba de Sanctum frente a `sesiones_token` (pospuesta).

Decisiones candidatas no aprobadas en este cierre: D-118 a D-122.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`
- `docs/06-architecture/cierre-fase-3b2-configuracion-postgresql.md`
- `docs/06-architecture/estrategia-pruebas-postgresql.md`
