# CREDIMEX — Diseño de la subfase 3B.3.1C

**Subfase:** Datos iniciales RBAC
**Fecha:** 16 de septiembre de 2026
**Estado:** Diseño funcional y mecanismo técnico aprobados.
  Implementación pendiente.
**Versión documental:** v2.6

Este documento **no** es un cierre de subfase. No afirma código nuevo
ni filas en PostgreSQL.

## Objetivo

Congelar el manifiesto de datos iniciales RBAC y el mecanismo de carga
owner-aware, como refinamiento de D-121, antes de implementar
`2026_09_16_000004_insert_initial_rbac_catalog.php`.

## Alcance aprobado

Incluye el diseño de:

- 3 roles (`cobrador`, `supervisor`, `administrador`);
- 13 permisos;
- 35 relaciones explícitas en `rol_permisos`;
- carga por migración de datos `pgsql_owner`;
- idempotencia fail-closed y `down()` selectivo;
- pruebas futuras mediante D-120 `runFiles()` de cuatro archivos.

No incluye implementación, seeders Laravel, grants `INSERT` al rol app,
usuarios, dispositivos, Sanctum ni autenticación.

## Manifiesto

La fuente canónica del catálogo, las descripciones, la matriz y las
reglas de carga es:

`docs/07-decisions/CREDIMEX_Decisiones_Catalogo_Inicial_RBAC_v2.6.md`

Resumen cuantitativo:

```text
3 roles
13 permisos
9 relaciones cobrador
13 relaciones supervisor
13 relaciones administrador
35 relaciones en total
```

## Archivos futuros (no creados)

| Tipo | Ruta prevista |
|---|---|
| Migración de datos | `backend/database/migrations/2026_09_16_000004_insert_initial_rbac_catalog.php` |
| Tests | `backend/tests/Feature/Database/Migrations/RbacInitialDataMigrationTest.php` (nombre exacto a confirmar en la implementación) |

No se usará `DatabaseSeeder.php`. No se modificará D-120.

El orden de `runFiles()` previsto es:

```text
000001 create_roles_table
000002 create_permisos_table
000003 create_rol_permisos_table
000004 insert_initial_rbac_catalog
```

## Privilegios

App permanece con `SELECT` sobre `roles`, `permisos` y
`rol_permisos`. La carga corre con `pgsql_owner`. 3B.3.1C no amplía
grants.

## Persistencia

Esta versión documental no despliega datos en `credimex_dev` ni en
producción. El inventario permanece en 67 tablas lógicas.

## Límites

D-122 continúa pendiente. 3B.3.2 (usuarios y dispositivos) queda fuera.

## Siguiente paso

Implementar 3B.3.1C **solo** cuando se autorice código, siguiendo el
manifiesto v2.6 sin ampliar el catálogo.

## Impacto en manuales

No se actualizan manuales funcionales: los datos iniciales todavía no
están implementados ni probados.

El impacto técnico futuro (guía del backend) se registrará al cerrar
la implementación.
