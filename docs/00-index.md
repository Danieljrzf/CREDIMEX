# CREDIMEX — Índice documental oficial

Este archivo es la **fuente canónica de rutas** de la documentación de CREDIMEX.
Ningún agente ni colaborador debe usar rutas de carpetas distintas a las declaradas aquí.

## Estado del proyecto

El proyecto se encuentra en **fase de diseño técnico (Fase 3A)**.
La **Fase 3A.3** (modelo físico preliminar PostgreSQL) está documentada
y aprobada a nivel documental; todavía **no** se crean migraciones ni
código.

El código todavía **no debe iniciarse**: no se crean proyectos Android ni Laravel,
no se generan migraciones y no se instalan dependencias hasta que el modelo de datos,
las transacciones, la idempotencia y la auditoría estén aprobados.

## Documento maestro vigente

- `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`

Este es el único documento maestro válido. Cualquier referencia a
`CREDIMEX_Documento_Maestro_v1.2.md` u otras variantes de ruta es incorrecta.

## Carpetas oficiales

```text
docs/
├── 00-index.md          Índice canónico (este archivo)
├── 00-product/          Visión de producto
├── 01-requirements/     Requerimientos y decisiones resueltas
├── 02-use-cases/        Casos de uso
├── 03-processes/        Diagramas de procesos
├── 04-database/         Modelo de datos, ERD y diccionario
├── 05-api/              Contrato de API (OpenAPI)
├── 06-architecture/     Arquitectura y decisiones técnicas
├── 07-decisions/        Registros de decisión de arquitectura (ADR)
└── archive/             Material histórico o superado
```

## Documentos aprobados

| Documento | Ruta | Estado |
|---|---|---|
| Documento maestro de producto, requerimientos y desarrollo v1.2 | `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md` | Aprobado |
| Historial documental | `docs/00-historial-documentacion.md` | Aprobado |
| Decisiones resueltas v1.3 | `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md` | Aprobado |
| Decisiones de modelo de datos v1.4 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md` | Aprobado |
| Decisiones de modelo de datos v1.5 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md` | Aprobado |
| Diferencias de caja | `docs/01-requirements/cash-differences.md` | Aprobado |
| Jornada del cobrador | `docs/01-requirements/collector-workday.md` | Aprobado |
| Puesta en marcha y carga inicial | `docs/03-processes/puesta-en-marcha-carga-inicial.md` | Aprobado |
| UC-24 — Registrar fondo entregado al cobrador | `docs/02-use-cases/UC-24-fondo-a-cobrador.md` | Aprobado |
| UC-25 — Operar caja central | `docs/02-use-cases/UC-25-caja-central.md` | Aprobado |
| UC-26 — Castigar crédito | `docs/02-use-cases/UC-26-castigar-credito.md` | Aprobado |
| UC-27 — Excepción de permanencia de efectivo | `docs/02-use-cases/UC-27-excepcion-permanencia-efectivo.md` | Aprobado |
| Catálogo de entidades | `docs/04-database/catalogo-entidades.md` | Aprobado |
| Catálogo de estados | `docs/04-database/catalogo-estados.md` | Aprobado |
| Catálogo de operaciones financieras | `docs/04-database/catalogo-operaciones-financieras.md` | Aprobado |
| Relaciones conceptuales | `docs/04-database/relaciones-conceptuales.md` | Aprobado |
| Modelo de movimientos financieros | `docs/04-database/modelo-movimientos-financieros.md` | Aprobado |
| Riesgos y decisiones abiertas | `docs/04-database/riesgos-y-decisiones-abiertas.md` | Aprobado |
| Decisiones de modelo lógico v1.6 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md` | Aprobado |
| Inventario de tablas lógicas | `docs/04-database/inventario-tablas-logicas.md` | Aprobado |
| Diccionario identidad, clientes y rutas | `docs/04-database/diccionario-identidad-clientes-rutas.md` | Aprobado |
| Diccionario créditos, calendarios y pagos | `docs/04-database/diccionario-creditos-calendarios-pagos.md` | Aprobado |
| Diccionario cobranza, caja y entregas | `docs/04-database/diccionario-cobranza-caja-entregas.md` | Aprobado |
| Diccionario libro, auditoría y procesos | `docs/04-database/diccionario-libro-auditoria-procesos.md` | Aprobado |
| Claves, relaciones y restricciones | `docs/04-database/claves-relaciones-restricciones.md` | Aprobado |
| Índices conceptuales | `docs/04-database/indices-conceptuales.md` | Aprobado |
| ERD conceptual | `docs/04-database/erd-conceptual.md` | Aprobado |
| Riesgos del modelo lógico | `docs/04-database/riesgos-modelo-logico.md` | Aprobado |
| Decisiones de modelo físico v1.7 | `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md` | Aprobado |
| Modelo físico preliminar PostgreSQL | `docs/04-database/modelo-fisico-postgresql.md` | Aprobado |
| Identificadores y exposición | `docs/04-database/identificadores-y-exposicion.md` | Aprobado |
| Restricciones físicas PostgreSQL | `docs/04-database/restricciones-fisicas-postgresql.md` | Aprobado |
| Borrado, inactivación y retención | `docs/04-database/borrado-inactivacion-y-retencion.md` | Aprobado |
| Sensibilidad, cifrado y logs | `docs/04-database/sensibilidad-cifrado-y-logs.md` | Aprobado |
| Convenciones físicas y orden de migraciones | `docs/04-database/convenciones-fisicas-y-orden-migraciones.md` | Aprobado |
| Riesgos del modelo físico | `docs/04-database/riesgos-modelo-fisico.md` | Aprobado |

La versión documental **v1.7** (Fase 3A.3) incluye:

- decisiones D-69 a D-91;
- modelo físico preliminar PostgreSQL;
- 67 tablas lógicas conservadas;
- 17 tablas con `id_publico`;
- tipos físicos;
- concurrencia;
- cifrado;
- retención;
- orden **futuro** de migraciones (las migraciones **no** están creadas).

## Documentos en construcción

| Entregable | Ruta prevista | Estado |
|---|---|---|
| Arquitectura técnica detallada | `docs/06-architecture/` | En construcción |
| Contrato OpenAPI inicial | `docs/05-api/` | En construcción |
| ADR adicionales | `docs/07-decisions/` | En construcción |

## Reglas de uso de la documentación

- `docs/00-index.md` es la única fuente de rutas. No se deben inventar carpetas ni renombrar las existentes.
- Todo documento nuevo se registra en las tablas de este índice antes de considerarse oficial.
- El documento maestro no se modifica dentro de esta normalización documental.
- El código no debe iniciarse hasta recibir autorización expresa tras cerrar la Fase 3A.
- Las decisiones D-01 a D-20 están en `CREDIMEX_Decisiones_Resueltas_v1.3.md`.
- Las decisiones D-21 a D-32 están en `CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`.
- Las decisiones D-33 a D-53 están en `CREDIMEX_Decisiones_Modelo_Datos_v1.5.md`.
- Las decisiones D-54 a D-68 están en `CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`.
- Las decisiones D-69 a D-91 están en `CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`.
