# CREDIMEX — Extensión controlada del harness y base RBAC

**Versión:** 2.4
**Fecha:** 16 de septiembre de 2026
**Estado:** Aprobado
**Alcance:** Subfase 3B.3.1A.2 y cierre de decisiones previas a
3B.3.1B. No crea migraciones ni tablas del dominio.

**Commit técnico 3B.3.1A.2:** `9621ae1` —
`test: extender harness owner-aware para migraciones dependientes`

**Commit documental v2.4:** `0848014` —
`docs: documentar el cierre de la subfase 3B.3.1A.2`.

## 1. Propósito

Ampliar de forma compatible D-120 para probar escenarios ordenados de
hasta ocho archivos de migración dependientes sin duplicar su
lifecycle, y refinar D-121 antes de crear las tablas RBAC. D-120
permanece aprobada y no es reemplazada por esta extensión.

## 2. Extensión de D-120

`OwnerAwareMigrationTestHarness` incorpora:

```php
runFiles(
    array $absolutePaths,
    array $expectedAbsentTables,
    Closure $assertions,
): void
```

`runFile()` delega en `runFiles([$absolutePath], ...)`. Existe una sola
implementación del lifecycle.

### 2.1 Validación fail-closed antes de BEGIN

El orden obligatorio es:

1. `assertReady()`;
2. validar la lista no vacía y el máximo de ocho archivos;
3. resolver y validar todos los paths contra la allowlist;
4. rechazar paths canónicos duplicados;
5. validar los identificadores de objetos esperados y obtener nombres
   de migración únicos;
6. validar el historial `migrations` y capturar su baseline;
7. rechazar objetos esperados preexistentes;
8. exigir `transactionLevel('pgsql_owner') === 0`;
9. abrir una sola transacción exterior owner;
10. ejecutar los `up` ordenados mediante el Migrator;
11. ejecutar las assertions conjuntas;
12. ejecutar el rollback lógico inverso;
13. verificar registros, objetos, conexión y nivel transaccional;
14. ejecutar el `ROLLBACK` exterior obligatorio.

La API no acepta directorios, globs, conexiones, credenciales, roles ni
esquemas arbitrarios. No existe `COMMIT` exterior.

La captura del baseline mediante `migrationBatches()` se realiza dentro
de la frontera sanitizada de `assertPreconditions()`. Si el
driver o repositorio falla, el harness lanza una
`OwnerAwareMigrationException` en la etapa lógica `contexto`, sin
SQLSTATE, SQL, credenciales, host ni excepción previa sensible. El
fallo ocurre antes de `BEGIN` y antes de cualquier llamada a
`Migrator::run()`.

### 2.2 Ejecución

Laravel 13.22.0 ordena una llamada multi-path por nombre de migración,
no por el orden recibido. Por ello, el harness ejecuta una llamada real
de `Migrator::run([$path], ['step' => true])` por archivo, dentro de la
misma transacción exterior y en el orden entregado a `runFiles()`.

Cada archivo obtiene un batch temporal independiente. Después de cada
`up` se comprueba:

- retorno exacto del Migrator;
- fila temporal en `migrations`;
- batch identificable;
- historial igual al baseline más las migraciones del escenario.

El callback se ejecuta una sola vez y únicamente después de completar
todos los `up`.

### 2.3 Rollback

**Resultado de auditoría:** `ROLLBACK MULTI-FILE SEGURO`.

En un escenario exitoso, el rollback lógico se ejecuta en orden
estrictamente inverso, una migración por llamada:

```text
child → parent_b → parent_a
```

Antes de cada `rollback([$path], ['step' => 1])`, la última fila global
de `migrations` debe coincidir exactamente con nombre y batch
esperados. Después se valida el retorno exacto y el snapshot del
historial. Esto convierte en fallo explícito el comportamiento
silencioso `Migration not found` de Laravel.

Los snapshots deben conservar intactas todas las migraciones ajenas. Si
la migración esperada no es la última aplicable o aparece cualquier
anomalía, el rollback lógico se detiene.

Si falla cualquier `up`, no se intenta una limpieza lógica peligrosa
contra un estado incompleto. Si falla el callback o un rollback lógico,
no se intentan más `down()`. En todos esos caminos, el rollback exterior
es la barrera definitiva y elimina el escenario completo.

### 2.4 Garantías conservadas

La extensión no altera:

- entorno exclusivo `testing`;
- base `credimex_test`;
- default ordinaria `pgsql`;
- conexión owner `pgsql_owner`;
- roles reales `credimex_test_owner` / `credimex_test_app`;
- esquema y `search_path` exclusivos `credimex`;
- una transacción exterior, sin commit;
- rollback exterior obligatorio;
- restauración de default y nivel transaccional;
- errores sanitizados;
- ejecución serial;
- prohibiciones D-117/D-120.

## 3. Fixtures de validación

Las fixtures exclusivas de testing son:

- `0000_00_00_000001_create_zz_test_owner_multi_parent_a_table.php`;
- `0000_00_00_000002_create_zz_test_owner_multi_parent_b_table.php`;
- `0000_00_00_000003_create_zz_test_owner_multi_child_table.php`.

Crean, respectivamente, `zz_test_owner_multi_parent_a`,
`zz_test_owner_multi_parent_b` y `zz_test_owner_multi_child`. La tercera
contiene FK `NO ACTION` hacia ambas primeras. No pertenecen al
inventario de 67 tablas y no dejan tablas ni filas persistentes en
`migrations`.

## 4. D-121 — Base RBAC

**Estado:** aprobada con alcance refinado.

D-121 autoriza la implementación futura de:

- **3B.3.1B:** DDL de `roles`, `permisos` y `rol_permisos`, incluidos
  constraints, índices, grants `SELECT` y pruebas mediante D-120. No
  incluye datos iniciales;
- **3B.3.1C:** datos iniciales RBAC: roles confirmados, catálogo de
  permisos y matriz `rol_permisos`. Su diseño específico se cerrará
  antes de implementarlo.

`usuarios` y `dispositivos` quedan en 3B.3.2 y requerirán una decisión
posterior separada. D-121 no incluye `sesiones_token`, Sanctum ni
autenticación. D-122 conserva ese alcance y continúa pendiente.

Las tablas RBAC todavía no existen.

### 4.1 Columnas cerradas

- `roles`: `id BIGINT GENERATED ALWAYS AS IDENTITY`,
  `codigo VARCHAR(64) NOT NULL`, `nombre VARCHAR(120) NOT NULL`,
  `activo BOOLEAN NOT NULL DEFAULT TRUE`,
  `created_at TIMESTAMPTZ NOT NULL` sin `DEFAULT` y
  `updated_at TIMESTAMPTZ NOT NULL` sin `DEFAULT`;
- `permisos`: `id BIGINT GENERATED ALWAYS AS IDENTITY`,
  `codigo VARCHAR(96) NOT NULL`, `modulo VARCHAR(64) NOT NULL`,
  `descripcion TEXT NULL`, `activo BOOLEAN NOT NULL DEFAULT TRUE`,
  `created_at TIMESTAMPTZ NOT NULL` sin `DEFAULT` y
  `updated_at TIMESTAMPTZ NOT NULL` sin `DEFAULT`;
- `rol_permisos`: `id BIGINT GENERATED ALWAYS AS IDENTITY`,
  `rol_id BIGINT NOT NULL`, `permiso_id BIGINT NOT NULL` y
  `created_at TIMESTAMPTZ NOT NULL` sin `DEFAULT`.

Las tres tablas carecen de `id_publico` y `deleted_at`.
`rol_permisos` tampoco lleva `activo` ni `updated_at`.

`created_at` y `updated_at` serán `TIMESTAMPTZ NOT NULL` **sin
DEFAULT**. No se utilizará `DEFAULT CURRENT_TIMESTAMP`. La aplicación o
el seeder futuro proporcionarán siempre el valor. No se introducen
triggers; `updated_at` deberá actualizarse explícitamente mediante la
lógica de aplicación cuando corresponda.

### 4.2 Relaciones y borrado

`rol_permisos.rol_id` referencia `roles.id` y
`rol_permisos.permiso_id` referencia `permisos.id`. Ambas FK serán
`ON UPDATE NO ACTION`, `ON DELETE NO ACTION` y no diferibles. No se
utiliza `CASCADE`. Roles y permisos se inactivan mediante `activo`; las
asignaciones `rol_permisos` pueden eliminarse físicamente de forma
controlada.

### 4.3 Constraints e índices

- `roles_pkey`;
- `uq_roles_codigo`;
- `chk_roles_codigo`;
- `permisos_pkey`;
- `uq_permisos_codigo`;
- `chk_permisos_codigo`;
- `chk_permisos_modulo`;
- `rol_permisos_pkey`;
- `uq_rol_permisos_rol_id_permiso_id`;
- `fk_rol_permisos_rol_id`;
- `fk_rol_permisos_permiso_id`;
- `idx_rol_permisos_permiso_id`.

Todos están por debajo del límite PostgreSQL de 63 bytes.

#### Expresiones CHECK cerradas para 3B.3.1B

La regex conceptual y la expresión que debe recibir PostgreSQL quedan
definidas por separado. El escape concreto dentro de una cadena PHP se
resolverá durante la implementación.

`roles.codigo`:

- regex conceptual: `^[a-z][a-z0-9_]*$`;
- expresión PostgreSQL conceptual:
  `CHECK ("codigo" ~ '^[a-z][a-z0-9_]*$')`;
- constraint: `chk_roles_codigo`;
- ejemplos válidos: `cobrador`, `supervisor`, `administrador`,
  `cobranza_diaria`;
- ejemplos inválidos: `Administrador`, `_cobrador`,
  `cobrador-activo`, `cobrador activo`.

`permisos.modulo`:

- regex conceptual: `^[a-z][a-z0-9_]*$`;
- expresión PostgreSQL conceptual:
  `CHECK ("modulo" ~ '^[a-z][a-z0-9_]*$')`;
- constraint: `chk_permisos_modulo`;
- ejemplos válidos: `clientes`, `pagos`, `creditos`, `caja_diaria`;
- ejemplos inválidos: `Clientes`, `_clientes`, `clientes-activos`,
  `clientes activos`.

`permisos.codigo`:

- regex conceptual:
  `^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$`;
- expresión PostgreSQL conceptual:
  `CHECK ("codigo" ~ '^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$')`;
- constraint: `chk_permisos_codigo`;
- ejemplos válidos: `clientes.registrar`, `clientes.consultar`,
  `pagos.registrar`, `pagos.reversar`, `creditos.autorizar`;
- ejemplos inválidos: `clientes registrar`, `clientes-registrar`,
  `clientesXregistrar`, `.clientes`, `clientes.`,
  `clientes..registrar`, `clientes.registrar.extra`,
  `Clientes.registrar`, `clientes.Registrar`.

En `permisos.codigo`, `\.` exige un punto literal entre módulo y acción.
No se admite `.` sin escape porque permitiría cualquier carácter como
separador.

### 4.4 Privilegios

En 3B.3.1B el rol app recibe exclusivamente `SELECT` sobre las tres
tablas. No recibe `INSERT`, `UPDATE`, `DELETE` ni `USAGE` sobre
secuencias. Tampoco recibe privilegios sobre `migrations` ni
`migrations_id_seq`.

Las migraciones usarán
`PostgreSqlGrantManager::fromOwnerMigration(app())`, con simetría
`GRANT` / `REVOKE`. No utilizarán `fromApplication()` dentro del
Migrator owner.

### 4.5 Datos iniciales

3B.3.1B no crea seeds ni inserta roles. Los únicos roles confirmados
para el diseño posterior de 3B.3.1C son:
`cobrador`, `supervisor` y `administrador`, además del catálogo de
permisos y su matriz. No existe rol `coordinador` aprobado.

**Actualización v2.6:** el manifiesto de 3B.3.1C (3 roles, 13 permisos,
35 relaciones, carga owner-aware, sin `DatabaseSeeder`) quedó cerrado
en `CREDIMEX_Decisiones_Catalogo_Inicial_RBAC_v2.6.md`. La
implementación permanece pendiente.

## 5. Validación

- unitarias del harness: 34 pruebas / 325 assertions;
- unitarias de `PostgreSqlGrantManager`: 47 pruebas / 122 assertions;
- integración owner-aware, corrida 1: 2 pruebas / 75 assertions;
- integración owner-aware, corrida 2: 2 pruebas / 75 assertions;
- suite completa: 106 pruebas / 603 assertions;
- Pint aprobado sobre los archivos PHP modificados;
- `git diff --check` limpio;
- ejecución multiarchivo real en PostgreSQL sin fixtures ni filas
  residuales;
- baseline histórico de `migrations` intacto;
- default final `pgsql`;
- `transactionLevel('pgsql_owner')` final `0`;
- rol app sin privilegios sobre `migrations` ni
  `migrations_id_seq`;
- `backend/database/migrations` sin migraciones de dominio.

## 6. Estado resultante

- D-117, D-118, D-119 y D-120: aprobadas;
- extensión multiarchivo de D-120: aprobada;
- D-121: aprobada con alcance RBAC refinado;
- D-122: pendiente;
- 3B.3.1A.2: completada;
- siguiente subfase al cierre de v2.4: 3B.3.1B.

**Actualización v2.5:** las migraciones RBAC se implementaron en el
repositorio (`c790e8d`). 3B.3.1B quedó completada. D-121 no cambió de
alcance. La siguiente subfase vigente es 3B.3.1C.

**Actualización v2.6:** el diseño específico de 3B.3.1C quedó cerrado
como refinamiento de D-121. La carga inicial no usará
`DatabaseSeeder`; usará una migración de datos owner-aware. El
manifiesto (3 roles, 13 permisos, 35 relaciones) está en
`docs/07-decisions/CREDIMEX_Decisiones_Catalogo_Inicial_RBAC_v2.6.md`.
La implementación permanece pendiente. Este documento conserva el
cierre histórico de 3B.3.1A.2.
