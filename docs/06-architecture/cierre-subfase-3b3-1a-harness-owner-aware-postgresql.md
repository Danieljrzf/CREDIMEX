# CREDIMEX — Cierre de la subfase 3B.3.1A

**Estado:** Completada técnica y documentalmente
**Fecha:** 23 de agosto de 2026
**Versión documental:** v2.3
**Decisión:** D-120
**Alcance:** Harness owner-aware fail-closed para prueba y rollback
controlado de migraciones PostgreSQL. Sin migraciones ni tablas del
dominio. No cierra la Fase 3B.3 completa.

---

## 1. Criterio de cierre

La subfase **3B.3.1A** queda cerrada porque existe un mecanismo
automatizado que:

- valida de forma fail-closed el contexto de testing;
- acepta un único archivo de migración permitido;
- ejecuta el `Migrator` real con `pgsql_owner`;
- contiene `up`, assertions y `down` en una transacción exterior;
- revierte siempre esa transacción;
- comprueba que no queden objetos ni filas residuales.

D-120 no recrea la base y no reemplaza la guarda D-117.

La Fase 3B.3 continúa en curso.

---

## 2. Commit técnico

| Campo | Valor |
|---|---|
| Hash | `4dcdd59` |
| Mensaje | `test: implementar harness owner-aware de migraciones PostgreSQL` |
| Rama | `feat/migraciones-seguridad-catalogos` |

---

## 3. Componentes implementados

| Componente | Responsabilidad |
|---|---|
| `OwnerAwareMigrationTestHarness` | Orquestar contexto, path, transacción, Migrator, rollback y cleanup |
| `OwnerAwareMigrationException` | Errores sanitizados por etapa lógica |
| `OwnerAwareMigrationInspection` | Inspección read-only de objetos, ownership, identity, historial y privilegios |
| Fixture `zz_test_owner_migration_harness` | Demostrar `up`, grants, `down` y ausencia de residuos |
| `PostgreSqlGrantManager::fromOwnerMigration()` | Compatibilidad centralizada entre Migrator y D-118 |
| Pruebas unitarias e integración | Validar fallos, lifecycle y PostgreSQL real |

---

## 4. Arquitectura final

```text
Tests\TestCase / D-117
        ↓
OwnerAwareMigrationTestHarness
        ↓
assertReady()
        ↓
archivo individual canónico
        ↓
precondiciones persistentes
        ↓
transactionLevel(pgsql_owner) == 0
        ↓
BEGIN exterior
        ↓
Migrator real con pgsql_owner
        ↓
up + registro temporal migrations
        ↓
assertions read-only
        ↓
rollback Migrator / down
        ↓
ausencia interna de objeto y registro
        ↓
ROLLBACK exterior en finally
        ↓
ausencia persistente + estado restaurado
```

No existe commit exterior.

---

## 5. Orden fail-closed

El orden final de `runFile()` es:

1. validar D-117/D-118 mediante `assertReady()`;
2. validar el path;
3. validar nombres y precondiciones;
4. comprobar el nivel transaccional;
5. abrir la transacción exterior;
6. ejecutar el `Migrator`;
7. ejecutar assertions;
8. ejecutar rollback del `Migrator`;
9. verificar ausencia interna;
10. ejecutar cleanup.

La prueba `contexto inseguro + path inválido` confirma que el error de
contexto ocurre antes que el error de path.

---

## 6. Contexto y conexiones

| Paso | Conexión |
|---|---|
| Aplicación ordinaria | `pgsql` |
| Contexto y precondiciones owner | `pgsql_owner` |
| Transacción exterior | `pgsql_owner` |
| `Migrator`, repositorio y DDL | `pgsql_owner` |
| Inspección del rol app | `pgsql` (read-only) |
| Metadatos de objetos y privilegios | `pgsql_owner` (read-only) |

Entorno permitido: `testing`; base: `credimex_test`; schema y
`search_path`: `credimex`.

---

## 7. Ownership transaccional

Antes de `BEGIN`, el harness exige:

```text
transactionLevel('pgsql_owner') = 0
```

Si existe una transacción previa, falla sin DDL y no intenta revertirla.
El harness abre exactamente un nivel propio, registra el nivel inicial
y vuelve exactamente a ese nivel en cleanup.

Los savepoints internos creados por Laravel quedan subordinados al
`BEGIN` exterior. Está prohibido hacer rollback repetitivo de niveles
cuyo ownership no pueda demostrarse.

---

## 8. Restauración de default connection

Laravel 13 cambia temporalmente la default a `pgsql_owner` al ejecutar
una migración owner. El harness captura la default previa y la restaura
en éxito y excepción.

Resultado validado:

```text
default inicial = pgsql
default final   = pgsql
```

---

## 9. Compatibilidad con grants

La aplicación ordinaria usa:

```php
PostgreSqlGrantManager::fromApplication(app());
```

Las migraciones owner usan:

```php
PostgreSqlGrantManager::fromOwnerMigration(app());
```

La segunda factory exige runtime default `pgsql_owner` y transacción
owner activa; cambia temporalmente a `pgsql` para delegar en
`fromApplication()` y restaura `pgsql_owner` en `finally`.

Las migraciones no deben alternar defaults manualmente ni implementar
sus propios `try/finally`. Los grants y revokes continúan ejecutándose
exclusivamente con `pgsql_owner`.

---

## 10. Paths permitidos

Roots:

- `backend/database/migrations/`;
- `backend/tests/Fixtures/migrations/`.

El harness acepta un solo archivo `.php` real y canónico. Rechaza
directorios, inexistentes, extensiones distintas, traversal, paths
externos y symlinks que escapen del root.

No puede ejecutar automáticamente todas las migraciones de un
directorio.

---

## 11. Repositorio `migrations`

El `Migrator` usa el repositorio técnico real mediante `pgsql_owner`.

Durante el escenario:

1. `up()` crea el objeto;
2. el registro aparece temporalmente en `migrations`;
3. rollback ejecuta `down()`;
4. el registro objetivo desaparece;
5. el rollback exterior elimina cualquier cambio residual.

El harness no crea ni elimina el repositorio, no usa
`deleteRepository()` y no modifica otras filas.

El rol app permanece sin privilegios sobre `migrations` y
`migrations_id_seq`.

---

## 12. Fixture smoke

Archivo:

`backend/tests/Fixtures/migrations/0000_00_00_000000_create_zz_test_owner_migration_harness_table.php`

La fixture:

- crea una tabla exclusiva de testing;
- usa PK identity;
- comprueba schema y owner;
- concede únicamente `SELECT` mediante
  `PostgreSqlGrantManager::fromOwnerMigration(app())`;
- revoca `SELECT` en `down`;
- elimina exclusivamente su propia tabla.

No contiene SQL directo de privilegios, manipulación manual de la
default, limpieza global ni objetos del dominio.

La tabla no persiste y no forma parte de las 67 tablas CREDIMEX.

---

## 13. Inspección read-only

`OwnerAwareMigrationInspection` consulta:

- existencia de tablas;
- schema y owner;
- PK e identity;
- presencia del registro objetivo en `migrations`;
- `has_table_privilege`;
- `has_sequence_privilege`.

No ejecuta DDL ni DML de negocio.

La comprobación de grants ocurre desde la conexión owner en la misma
transacción. No se intenta `SELECT` real mediante la conexión app antes
de commit. D-120 no incluye modo committed.

---

## 14. Cleanup y excepciones

El rollback exterior se intenta siempre desde `finally`.

Se verifican:

- nivel transaccional final;
- default connection final;
- ausencia persistente de la tabla fixture;
- ausencia persistente de su fila en `migrations`.

Los errores solo identifican contexto, path, up, assertions, down o
cleanup. No exponen password, host, DSN, SQL, SQLSTATE ni mensajes
completos del driver.

---

## 15. Concurrencia

Los tests están clasificados como **SERIAL ONLY**. Comparten
`credimex_test`, schema `credimex`, nombres de objetos y repositorio
`migrations`.

No se habilita parallel testing.

---

## 16. Prohibiciones

D-120 no utiliza:

- `RefreshDatabase`;
- `DatabaseMigrations`;
- `DatabaseTruncation`;
- `DatabaseTransactions`;
- `migrate:fresh`;
- `db:wipe`;
- `DROP SCHEMA`;
- Artisan `migrate` / `rollback` desde PHPUnit;
- truncado o limpieza general.

---

## 17. Evidencia de validación

| Validación | Resultado |
|---|---|
| `php -l` | 8 archivos sin errores |
| Unitarias harness | 20 passed / 88 assertions |
| Unitarias `PostgreSqlGrantManager` | 47 passed / 122 assertions |
| Smoke corrida 1 | 1 passed / 28 assertions |
| Smoke corrida 2 | 1 passed / 28 assertions |
| Suite completa | 91 passed / 319 assertions |
| `git diff --check` | Limpio |

Ambos smoke confirmaron:

- tabla fixture ausente persistentemente;
- fila fixture ausente en `migrations`;
- default restaurada a `pgsql`;
- `transactionLevel('pgsql_owner')=0`;
- app sin privilegios sobre `migrations`;
- app sin privilegios sobre `migrations_id_seq`.

---

## 18. Estado de la subfase

- **3B.3.1.0:** auditoría de migraciones default confirmada; el
  directorio ya estaba vacío y no requirió cambios.
- **3B.3.1A:** completada técnica y documentalmente.
- **D-120:** aprobada.
- **D-121:** candidata.
- **D-122:** candidata.

No existen todavía migraciones de roles, permisos, `rol_permisos`,
usuarios, dispositivos o `sesiones_token`. Tampoco existen seeds RBAC
ni instalación de Sanctum.

---

## 19. Siguiente paso

**3B.3.1B — migraciones de roles, permisos y rol_permisos.**

El diseño previsto mantiene grants app iniciales de solo `SELECT` y sin
`USAGE` de secuencias. No se implementa en este cierre.

Después quedará **3B.3.1C — datos iniciales RBAC**.

---

## 20. Impacto en manuales

Esta subfase impactará posteriormente:

- Manual técnico: ejecución segura de pruebas de migraciones;
- Guía de contribución: harness obligatorio, serialización y paths;
- Manual de incidencias: diagnóstico de fallos por etapa.

No se actualizan manuales funcionales porque todavía no existen tablas
ni funciones de dominio.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Harness_Migraciones_PostgreSQL_v2.3.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Helper_Privilegios_PostgreSQL_v2.2.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Guarda_Pruebas_PostgreSQL_v2.1.md`
- `docs/06-architecture/cierre-subfase-3b3-0b-helper-privilegios-postgresql.md`
