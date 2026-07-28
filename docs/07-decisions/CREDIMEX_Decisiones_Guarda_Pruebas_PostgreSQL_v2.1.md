# CREDIMEX — Decisiones de Guarda de Pruebas PostgreSQL

**Versión:** 2.1
**Fecha:** 28 de julio de 2026
**Estado:** Aprobado
**Alcance:** Subfase 3B.3.0A — Guarda PHPUnit fail-closed para
PostgreSQL. No modifica D-01 a D-116 ni el inventario de 67 tablas.
**Relación:** Complementa D-113 y D-100; no cierra la Fase 3B.3.

---

## Propósito

Documentar la decisión D-117 que fija la guarda fail-closed de PHPUnit
ya implementada en el backend, su integración en `Tests\TestCase`, el
rechazo de traits estándar de recreación de base y el tratamiento
sanitizado de fallos de inspección.

Entregable asociado:

- `docs/06-architecture/cierre-subfase-3b3-0a-guarda-postgresql-fail-closed.md`

---

## D-117 — Guarda PHPUnit fail-closed para PostgreSQL

**Decisión:**

- Toda prueba Laravel que extienda `Tests\TestCase` debe pasar por la
  guarda `Tests\Support\PostgreSqlTestSafetyGuard` antes de continuar
  el ciclo de traits del framework.
- La integración ocurre en `Tests\TestCase::setUpTraits()`:
  - se obtiene el inventario de traits
    (`$this->traitsUsedByTest ?? class_uses_recursive(static::class)`);
  - se rechazan traits peligrosos;
  - se ejecuta la guarda;
  - solo si todo es correcto se llama a `return parent::setUpTraits()`.
- `setUpTraits()` no declara retorno `: void`.
- No se sobrescribe `setUp()` para esta protección.
- La aplicación Laravel ya está inicializada cuando corre la guarda, de
  modo que puede leer configuración y abrir conexiones de solo lectura.
- Validación obligatoria fail-closed:
  - `APP_ENV` / entorno Laravel exactamente `testing`;
  - conexión default exactamente `pgsql`;
  - driver `pgsql` en `pgsql` y en `pgsql_owner`;
  - contexto real de `pgsql`: base `credimex_test`, usuario
    `credimex_test_app`, esquema `credimex`, `search_path` exactamente
    `credimex`, `current_schemas(false)::text` exactamente `{credimex}`;
  - contexto real de `pgsql_owner`: base `credimex_test`, usuario
    `credimex_test_owner`, esquema `credimex`, `search_path`
    exactamente `credimex`, `current_schemas(false)::text` exactamente
    `{credimex}`.
- Los valores esperados son constantes de seguridad del entorno testing;
  no se derivan de `DB_DATABASE` ni de `DB_USERNAME`.
- Quedan prohibidos, hasta implementar un mecanismo propio que migre
  con `pgsql_owner`:
  - `Illuminate\Foundation\Testing\RefreshDatabase`;
  - `Illuminate\Foundation\Testing\DatabaseMigrations`;
  - `Illuminate\Foundation\Testing\DatabaseTruncation`.
- El rechazo de esos traits ocurre antes de `parent::setUpTraits()`.
- Si la guarda falla, no se llama a `parent::setUpTraits()` y no debe
  ejecutarse DDL ni DML de la suite.
- Los fallos de inspección de conexión se convierten en
  `PostgreSqlTestSafetyException` con mensaje controlado, sin exponer
  contraseña, DSN, host interno, SQL original ni mensajes del
  controlador.
- Las pruebas unitarias de la guarda extienden directamente
  `PHPUnit\Framework\TestCase` (no `Tests\TestCase`), para no disparar
  la guarda antes del cuerpo de la prueba.
- Existe una única prueba de integración positiva de solo lectura que
  extiende `Tests\TestCase` y comprueba el entorno testing real.
- No desactivar ni evadir la guarda fail-closed.

**Estado de implementación:** implementada en el commit técnico
`51527f7` — `test: implementar guarda PostgreSQL fail-closed`.

**Motivo:** Materializar el requisito fail-closed de D-113, impedir que
una configuración incorrecta toque `credimex_dev` u otros entornos, y
bloquear traits de recreación que migrarían con la conexión ordinaria
antes de existir un mecanismo propio con `pgsql_owner`.

---

## Candidatas pendientes (no aprobadas)

Las siguientes no forman parte de v2.1 y **no** están aprobadas:

- **D-118 (candidata):** configuración y helper seguro de grants /
  subfase 3B.3.0B.
- **D-119 (candidata):** resolución de roles receptores por entorno
  (`DB_APP_ROLE` u equivalente).
- **D-120 (candidata):** trait propio de migraciones / recreación de
  base con `pgsql_owner`.
- **D-121 (candidata):** migraciones iniciales de seguridad
  (roles, permisos, usuarios, dispositivos).
- **D-122 (candidata):** prueba de Sanctum frente a `sesiones_token`.

---

## Relación con D-01 a D-116

Esta decisión **no altera** D-01 a D-116 ni el inventario de 67 tablas.
Implementa en código el requisito de guarda previsto en D-113 para la
subfase 3B.3.0A. No cierra la Fase 3B.3 completa.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Configuracion_PostgreSQL_v2.0.md`
- `docs/06-architecture/cierre-subfase-3b3-0a-guarda-postgresql-fail-closed.md`
- `docs/06-architecture/estrategia-pruebas-postgresql.md`
- `docs/06-architecture/cierre-fase-3b2-configuracion-postgresql.md`
