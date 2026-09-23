# CREDIMEX — Cierre de la subfase 3B.3.1B

**Subfase:** Migraciones RBAC iniciales
**Fecha:** 16 de septiembre de 2026
**Estado:** Completada técnica y documentalmente
**Versión documental:** v2.5

**Commit técnico 3B.3.1B:** `c790e8d` —
`feat: implementar migraciones RBAC iniciales`

**Commit documental v2.5:** `214d1ba` —
`docs: documentar el cierre de la subfase 3B.3.1B`.

## Objetivo

Implementar en el repositorio las primeras tres migraciones reales de
dominio (`roles`, `permisos` y `rol_permisos`) según D-121, y
validarlas exclusivamente mediante el harness owner-aware D-120.

## Alcance

Incluye DDL, constraints, índices, grants `SELECT` y pruebas. No
incluye datos iniciales, seeders, modelos, controladores, API, usuarios,
dispositivos, `sesiones_token`, Sanctum ni autenticación.

## Diseño implementado

Las tres tablas usan `$table->id()->generatedAs()->always()`. Los
timestamps son `TIMESTAMPTZ NOT NULL` sin default. Las FK de
`rol_permisos` son `ON UPDATE NO ACTION` / `ON DELETE NO ACTION`, no
diferibles y sin `CASCADE`.

Los CHECK regex se agregaron con SQL PostgreSQL estático, ejecutado por
`pgsql_owner` sobre el esquema `credimex`. Cada migración concede
únicamente `SELECT` mediante
`PostgreSqlGrantManager::fromOwnerMigration(app())`.

## Archivos implementados

- `backend/database/migrations/2026_09_16_000001_create_roles_table.php`;
- `backend/database/migrations/2026_09_16_000002_create_permisos_table.php`;
- `backend/database/migrations/2026_09_16_000003_create_rol_permisos_table.php`;
- `backend/tests/Feature/Database/Migrations/RbacMigrationsIntegrationTest.php`;
- `backend/tests/Support/Migrations/OwnerAwareMigrationInspection.php`.

## Arquitectura de pruebas

La integración usa `OwnerAwareMigrationTestHarness::runFiles()`:

```text
roles up
→ permisos up
→ rol_permisos up
→ assertions estructurales y funcionales
→ rol_permisos down
→ permisos down
→ roles down
→ rollback exterior
```

Las violaciones esperadas se aislaron con savepoints. La transacción
exterior continuó utilizable. La inspección se amplió solo con métodos
read-only: `columns()`, `constraintMetadata()`, `foreignKeyMetadata()`,
`indexMetadata()` e `identitySequence()`.

La prueba funcional de identity usa `identity_manual` como código
válido. El catálogo confirma `identity_generation = ALWAYS`.

## Validación

- unitarias harness: 34 pruebas / 325 assertions;
- unitarias PostgreSqlGrantManager: 47 pruebas / 122 assertions;
- integración owner-aware: 2 pruebas / 75 assertions;
- integración RBAC corrida 1: 1 prueba / 309 assertions;
- integración RBAC corrida 2: 1 prueba / 309 assertions;
- suite completa: 107 pruebas / 912 assertions;
- PHP lint correcto en los cinco archivos técnicos;
- Pint aprobado;
- `git diff --check` limpio;
- cero tablas RBAC residuales;
- cero filas residuales de estas migraciones;
- baseline histórico de `migrations` intacto;
- default connection restaurada;
- `transactionLevel('pgsql_owner') = 0`;
- app sin privilegios sobre `migrations` ni `migrations_id_seq`.

## Persistencia

Las migraciones existen en el repositorio. Esta subfase no las desplegó
persistentemente en `credimex_dev` ni en producción. D-120 las creó y
revirtió dentro de su escenario controlado.

`DatabaseSeeder.php` continúa sin datos RBAC. El inventario permanece
en 67 tablas lógicas.

## Límites

D-121 no cubre usuarios, dispositivos, `sesiones_token`, Sanctum ni
autenticación. D-122 continúa pendiente. 3B.3.1C cubrirá los datos
iniciales RBAC.

## Siguiente paso

**3B.3.1C — datos iniciales RBAC.**

Este cierre no crea seeders ni inicia 3B.3.1C.

**Actualización v2.6:** el diseño funcional y el mecanismo técnico de
3B.3.1C quedaron aprobados. La implementación permanece pendiente. Este
documento conserva el cierre histórico de 3B.3.1B. Ver
`docs/07-decisions/CREDIMEX_Decisiones_Catalogo_Inicial_RBAC_v2.6.md`.

## Impacto en manuales

Se actualiza la guía técnica del backend para registrar las tres
migraciones RBAC y su validación mediante D-120.

No se actualizan manuales funcionales: todavía no existen datos
iniciales ni funcionalidad de asignación de roles.
