# CREDIMEX — Decisiones de catálogo inicial RBAC v2.6

**Versión:** 2.6
**Fecha:** 16 de septiembre de 2026
**Estado:** Aprobado (diseño funcional y mecanismo técnico).
**Alcance:** Refinamiento de D-121 para congelar el contenido de
**3B.3.1C — datos iniciales RBAC**. No crea una decisión D-xxx nueva.
No modifica el significado de D-122.

**Implementación:** pendiente. Este documento no afirma que existan
filas en PostgreSQL ni que el archivo de carga esté creado.

## 1. Propósito

Registrar la aprobación funcional y técnica del catálogo inicial RBAC
antes de escribir código. 3B.3.1B permanece cerrada (DDL). 3B.3.1C
queda con diseño cerrado e implementación pendiente.

D-120 continúa **aprobada** y ampliada. D-121 continúa **aprobada**
para RBAC y se **refina** aquí. D-122 continúa **pendiente**.

## 2. Relación con D-121 y D-122

D-121, refinada en v2.4, autorizó:

- **3B.3.1B:** DDL de `roles`, `permisos` y `rol_permisos` (implementada
  en v2.5 / `c790e8d`);
- **3B.3.1C:** datos iniciales RBAC, con diseño específico a cerrar
  antes de implementar.

Este refinamiento v2.6 **cierra ese diseño**. No amplía D-121 a
usuarios, dispositivos, `sesiones_token`, Sanctum ni autenticación.

D-122 conserva su significado previo y permanece pendiente. No se crea
D-122 nueva ni se reutiliza ese número.

## 3. Estado de 3B.3.1C

| Aspecto | Estado |
|---|---|
| Diseño funcional | **Aprobado** |
| Mecanismo técnico de carga | **Aprobado** |
| Implementación (migración de datos, tests, SQL) | **Pendiente** |
| Filas persistentes en PostgreSQL | **No afirmadas** |
| Subfase completada | **No** |

## 4. Roles iniciales aprobados

Exactamente tres roles. Todos con `activo = true`.

| codigo | nombre | activo |
|---|---|---|
| `cobrador` | `Cobrador` | `true` |
| `supervisor` | `Supervisor` | `true` |
| `administrador` | `Administrador` | `true` |

`codigo` es la clave natural técnica. `nombre` es la etiqueta mostrada.

No se agregan `coordinador`, `operador`, `gerente`, `auditor`,
`superadmin` ni ningún otro rol.

## 5. Catálogo inicial aprobado — 13 permisos

Congelados **exactamente** estos códigos. Forma `modulo.accion`. Regex:

```text
^[a-z][a-z0-9_]*\.[a-z][a-z0-9_]*$
```

El prefijo de `codigo` coincide con `modulo`. Todos con `activo = true`.

| # | codigo | modulo | descripcion | activo |
|---|---|---|---|---|
| 1 | `clientes.consultar` | `clientes` | Consultar clientes | `true` |
| 2 | `clientes.registrar` | `clientes` | Registrar clientes | `true` |
| 3 | `clientes.editar` | `clientes` | Editar clientes | `true` |
| 4 | `creditos.consultar` | `creditos` | Consultar créditos | `true` |
| 5 | `creditos.autorizar` | `creditos` | Autorizar créditos | `true` |
| 6 | `creditos.renovar` | `creditos` | Renovar créditos | `true` |
| 7 | `pagos.registrar` | `pagos` | Registrar pagos | `true` |
| 8 | `caja.consultar` | `caja` | Consultar caja | `true` |
| 9 | `rutas.consultar` | `rutas` | Consultar rutas | `true` |
| 10 | `asignaciones.asignar` | `asignaciones` | Asignar cobradores | `true` |
| 11 | `asignaciones.reasignar` | `asignaciones` | Reasignar cobradores | `true` |
| 12 | `reportes.consultar` | `reportes` | Consultar reportes | `true` |
| 13 | `auditoria.consultar` | `auditoria` | Consultar auditoría | `true` |

Las descripciones no incluyen matices de alcance (asignados, territorio,
todos, parcial, completo).

## 6. Módulos exactos

`clientes`, `creditos`, `pagos`, `caja`, `rutas`, `asignaciones`,
`reportes`, `auditoria`.

No se introducen otros módulos en este catálogo inicial.

## 7. Matriz explícita — 35 relaciones

No existe herencia runtime. Cada fila de `rol_permisos` se materializa
de forma explícita.

### 7.1 Cobrador — 9 permisos

- `clientes.consultar`
- `clientes.registrar`
- `clientes.editar`
- `creditos.consultar`
- `creditos.autorizar`
- `creditos.renovar`
- `pagos.registrar`
- `caja.consultar`
- `rutas.consultar`

El cobrador **no** recibe `asignaciones.asignar`,
`asignaciones.reasignar`, `reportes.consultar` ni
`auditoria.consultar`.

### 7.2 Supervisor — 13 permisos

Los trece códigos del catálogo inicial.

### 7.3 Administrador — 13 permisos

Los trece códigos del catálogo inicial.

La coincidencia numérica supervisor/administrador **no** autoriza un
algoritmo de herencia.

### 7.4 Manifiesto cuantitativo

```text
9 + 13 + 13 = 35
```

El manifiesto de 3B.3.1C espera **35** filas en `rol_permisos` para los
tres roles iniciales y los trece permisos iniciales, y ninguna relación
adicional sobre esos tres roles.

| permiso | cobrador | supervisor | administrador |
|---|---|---|---|
| `clientes.consultar` | sí | sí | sí |
| `clientes.registrar` | sí | sí | sí |
| `clientes.editar` | sí | sí | sí |
| `creditos.consultar` | sí | sí | sí |
| `creditos.autorizar` | sí | sí | sí |
| `creditos.renovar` | sí | sí | sí |
| `pagos.registrar` | sí | sí | sí |
| `caja.consultar` | sí | sí | sí |
| `rutas.consultar` | sí | sí | sí |
| `asignaciones.asignar` | no | sí | sí |
| `asignaciones.reasignar` | no | sí | sí |
| `reportes.consultar` | no | sí | sí |
| `auditoria.consultar` | no | sí | sí |

## 8. Sin herencia automática

Queda aprobado:

- no existe jerarquía runtime `administrador hereda supervisor`;
- no existe jerarquía runtime `supervisor hereda cobrador`;
- no se crea tabla adicional de jerarquía.

Razones: auditabilidad, mínimo privilegio, compatibilidad con D-16,
excepciones futuras y ausencia de permisos implícitos.

## 9. Alcance de datos

`asignados`, `territorio` y `todos` son políticas o filtros
contextuales. **No** son permisos distintos.

La tabla `permisos` expresa **capacidad**. El alcance de registros
corresponde a lógica de negocio y autorización posterior.

No se crean `clientes.consultar_asignados`,
`clientes.consultar_territorio`, `clientes.consultar_todos` ni
equivalentes.

## 10. `creditos.autorizar`

Un único permiso. Responde «¿puede ejecutar la autorización?».

El límite del cobrador, incluido el rango inicial de $1,000 a $4,000
(D-12 / RN-AUT-001), es **regla de negocio**, no un segundo permiso.

No se crean `creditos.autorizar_hasta_4000` ni
`creditos.autorizar_sin_limite`.

## 11. `auditoria.consultar` y D-16

| rol | `auditoria.consultar` |
|---|---|
| cobrador | no |
| supervisor | sí |
| administrador | sí |

D-16 determina el **alcance** del supervisor: auditoría operativa
parcial / filtrada. El administrador consulta la auditoría completa
según el diseño funcional.

No se crea `auditoria.consultar_parcial`. D-16 no se convierte en otra
fila de `permisos`.

## 12. Exclusiones del catálogo inicial

Quedan **fuera de 3B.3.1C**. No están rechazadas de forma definitiva.

- `usuarios.*`
- `configuracion.*`
- `pagos.consultar`
- `evidencias.*`
- `cortes.*`
- `transferencias.*`
- `indicadores.consultar`
- `cartera.consultar`
- `liquidaciones.consultar`
- `tickets.*`
- `visitas.*`
- `desembolsos.*`
- permisos atomizados de UC-24..27

No se agregan permisos inferidos.

## 13. Mecanismo de carga aprobado

Migración de **datos** owner-aware. No `DatabaseSeeder`. No `db:seed`.
No grants `INSERT` al rol app.

Archivo futuro previsto (aún no creado; no se cambian los timestamps de
`000001`–`000003`):

```text
backend/database/migrations/2026_09_16_000004_insert_initial_rbac_catalog.php
```

- conexión administrativa: `pgsql_owner`;
- app conserva únicamente `SELECT` sobre `roles`, `permisos` y
  `rol_permisos`;
- la unidad de datos no amplía grants.

Orden conceptual de carga:

1. insertar roles por `codigo`;
2. insertar permisos por `codigo`;
3. resolver `id` de rol y de permiso por `codigo`;
4. insertar `rol_permisos`.

## 14. Identificadores

Prohibido asumir IDs fijos. No se documenta `cobrador = 1`,
`supervisor = 2`, `administrador = 3` ni IDs fijos de permisos.

Claves naturales: `roles.codigo` y `permisos.codigo`. Las relaciones se
resuelven después de leer los `id` generados.

## 15. Timestamps

Las columnas son `TIMESTAMPTZ NOT NULL` **sin DEFAULT**. La carga
utilizará **un único timestamp** consistente por ejecución:

- `roles`: `created_at` y `updated_at` explícitos;
- `permisos`: `created_at` y `updated_at` explícitos;
- `rol_permisos`: `created_at` explícito.

## 16. Idempotencia fail-closed

| Situación | Acción |
|---|---|
| La fila no existe | insertar |
| Existe y coincide **exactamente** con el manifiesto | aceptar sin `UPDATE` |
| Existe con contenido divergente | **abortar** |

Prohibido: upsert silencioso, insert-or-ignore, `UPDATE` automático,
`TRUNCATE` y reset de secuencias.

Aplica a `roles`, `permisos` y `rol_permisos`.

## 17. Comparación exacta

Para decidir equivalencia semántica **no** se usan `created_at` ni
`updated_at`. Los timestamps son metadata de creación.

| Entidad | Campos comparados |
|---|---|
| `roles` | `codigo`, `nombre`, `activo` |
| `permisos` | `codigo`, `modulo`, `descripcion`, `activo` |
| `rol_permisos` | par lógico `rol.codigo` + `permiso.codigo` |

## 18. Relaciones inesperadas

Si alguno de los tres roles iniciales tiene una relación que **no**
pertenece a las 35 del manifiesto, la validación del catálogo canónico
lo trata como **conflicto fail-closed**.

No se elimina automáticamente. No se ignora. No se sobrescribe. Un
despliegue no puede considerar válido un RBAC más amplio que el
aprobado.

## 19. `down()` / rollback

El `down()` de la migración de datos elimina **únicamente** filas del
manifiesto de 3B.3.1C, tras confirmar que coinciden exactamente.

Orden conceptual:

1. `rol_permisos` del manifiesto;
2. `permisos` del manifiesto;
3. `roles` del manifiesto.

Prohibido: `TRUNCATE`, `DELETE` global, reset de sequence, `CASCADE`
manual y borrar datos ajenos.

Si una FK futura impide eliminar: **abortar**. No inactivar en
silencio ni borrar dependencias ajenas.

Esto es rollback técnico controlado de despliegue/testing. No es
operación ordinaria del sistema: el runtime no borra el catálogo RBAC.

## 20. Testing futuro

Se reutiliza D-120 `runFiles()` **sin modificar el harness**. Cuatro
archivos explícitos:

1. `2026_09_16_000001_create_roles_table.php`
2. `2026_09_16_000002_create_permisos_table.php`
3. `2026_09_16_000003_create_rol_permisos_table.php`
4. `2026_09_16_000004_insert_initial_rbac_catalog.php`

Verificaciones previstas (no implementadas en esta versión
documental):

- exactamente 3 roles, 13 permisos y 35 relaciones;
- ausencia de `coordinador`;
- códigos, nombres, módulos, descripciones y `activo = true` exactos;
- timestamps no nulos;
- ninguna aserción sobre IDs numéricos conocidos;
- matriz exacta;
- segunda ejecución equivalente aceptada;
- divergencia rechazada;
- relaciones inesperadas rechazadas;
- `down()` no borra datos ajenos;
- cero residuos tras el escenario.

SERIAL ONLY. Base `credimex_test`. `pgsql_owner`. Sin
`RefreshDatabase`, `migrate:fresh` ni truncado general.

## 21. Inventario

Permanece en **67 tablas lógicas**. 3B.3.1C agrega **datos**, no
tablas. Las tres tablas RBAC ya estaban en el inventario.

## 22. Límites

Esta versión no crea el archivo `000004`, no inserta datos, no ejecuta
SQL, no ejecuta PHPUnit ni Artisan, y no afirma despliegue en
`credimex_dev` ni en producción.

Usuarios, dispositivos, Sanctum y autenticación permanecen en 3B.3.2 /
D-122 según su alcance previo.

## 23. Estado resultante

- D-120: aprobada y ampliada; **sin cambios** en esta versión;
- D-121: aprobada para RBAC; **refinada** con el manifiesto de 3B.3.1C;
- D-122: pendiente, sin cambio de significado;
- 3B.3.1B: sigue cerrada;
- 3B.3.1C: diseño funcional aprobado, mecanismo técnico aprobado,
  implementación pendiente.

No se crea tag. No se afirma merge ni push.
