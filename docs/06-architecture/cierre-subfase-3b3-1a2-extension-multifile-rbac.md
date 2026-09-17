# CREDIMEX — Cierre de la subfase 3B.3.1A.2

**Subfase:** Extensión controlada multiarchivo de D-120 y decisiones
previas a RBAC
**Fecha:** 16 de septiembre de 2026
**Estado:** Completada técnica y documentalmente
**Versión documental:** v2.4

**Commit técnico 3B.3.1A.2:** `9621ae1` —
`test: extender harness owner-aware para migraciones dependientes`

**Commit documental v2.4:** `0848014` —
`docs: documentar el cierre de la subfase 3B.3.1A.2`.

## Resultado

`OwnerAwareMigrationTestHarness` admite escenarios ordenados de uno a
ocho archivos mediante `runFiles()`. `runFile()` conserva su API y
delega en el mismo lifecycle. Esta ampliación es compatible con D-120,
que permanece aprobada.

La implementación:

- valida todos los archivos antes de `BEGIN`;
- rechaza listas vacías, exceso, paths y nombres duplicados;
- ejecuta cada archivo en el orden solicitado con el Migrator real;
- mantiene una sola transacción exterior owner;
- ejecuta assertions solo después de todos los `up`;
- revierte en orden inverso y verifica la última fila de `migrations`;
- detecta alteraciones ajenas mediante snapshots;
- usa exclusivamente rollback exterior en caminos de fallo;
- restaura default connection y transaction level;
- conserva mensajes sanitizados y ejecución serial.

La auditoría final determinó `ROLLBACK MULTI-FILE SEGURO`: cada rollback
lógico comprueba primero que la migración esperada sea la última
aplicable; ante cualquier anomalía detiene el ciclo y conserva el
rollback exterior como barrera definitiva. Un fallo parcial de `up` no
inicia limpieza lógica contra un estado incompleto.

La captura inicial de `migrationBatches()` quedó dentro de la frontera
sanitizada de `assertPreconditions()`. Un fallo del driver o repositorio
produce `OwnerAwareMigrationException` en una etapa controlada, sin
SQLSTATE, SQL, credenciales, host ni excepción previa sensible, y
ocurre antes de `BEGIN` y del Migrator.

## Validación dependiente

Tres fixtures exclusivas de testing prueban el escenario:

- `0000_00_00_000001_create_zz_test_owner_multi_parent_a_table.php`;
- `0000_00_00_000002_create_zz_test_owner_multi_parent_b_table.php`;
- `0000_00_00_000003_create_zz_test_owner_multi_child_table.php`.

La tercera depende de las dos primeras mediante FK `NO ACTION`. Ninguna
forma parte del inventario de 67 tablas.

```text
parent_a up
→ parent_b up
→ child up con dos FK
→ assertions conjuntas
→ child down
→ parent_b down
→ parent_a down
→ rollback exterior
```

Resultado:

- 34 unitarias del harness / 325 assertions;
- 47 unitarias de PostgreSqlGrantManager / 122 assertions;
- integración owner-aware, corrida 1: 2 pruebas / 75 assertions;
- integración owner-aware, corrida 2: 2 pruebas / 75 assertions;
- suite completa: 106 pruebas / 603 assertions;
- Pint aprobado sobre los archivos PHP modificados;
- `git diff --check` limpio;
- cero tablas fixture persistentes;
- cero filas fixture persistentes en `migrations`;
- baseline de migraciones históricas intacto;
- default connection restaurada;
- `transactionLevel('pgsql_owner')` final igual a `0`;
- app sin privilegios sobre `migrations` ni `migrations_id_seq`;
- `backend/database/migrations` sin migraciones de dominio.

## Decisiones RBAC cerradas

D-121 se refina y aprueba con alcance exclusivo de base RBAC, para
autorizar su implementación futura:

- 3B.3.1B: DDL de `roles`, `permisos`, `rol_permisos`, incluidos
  constraints, índices, grants `SELECT` y pruebas D-120, sin datos
  iniciales;
- 3B.3.1C: datos iniciales RBAC; su diseño específico se cerrará antes
  de implementarlo.

Se fijan:

- PK identity BIGINT y sin UUID;
- `activo` en roles y permisos;
- sin `activo` en `rol_permisos`;
- timestamps `TIMESTAMPTZ NOT NULL` sin default;
- FK `NO ACTION`;
- nombres explícitos de constraints e índice;
- grants app solo `SELECT`, sin `USAGE`;
- tres roles V1, sin coordinador.

Usuarios y dispositivos quedan fuera, para 3B.3.2 y una decisión
posterior. D-121 tampoco incluye `sesiones_token`, Sanctum ni
autenticación. D-122 continúa pendiente. Las tablas RBAC todavía no
existen.

## Siguiente paso

**3B.3.1B — migraciones de roles, permisos y rol_permisos.**

Este cierre no crea migraciones ni tablas del dominio y no ejecuta
seeds.

## Impacto en manuales

Se actualiza la guía técnica del backend para distinguir `runFile()` y
`runFiles()`, y para aclarar que el ejemplo amplio de grants no define
los privilegios RBAC.

No se actualizan manuales funcionales: todavía no existe funcionalidad
de dominio implementada.
