# CREDIMEX — Índice documental oficial

Este archivo es la **fuente canónica de rutas** de la documentación de CREDIMEX.
Ningún agente ni colaborador debe usar rutas de carpetas distintas a las declaradas aquí.

## Estado del proyecto

El proyecto se encuentra en **fase de diseño técnico (Fase 3A)**.

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
| Diferencias de caja | `docs/01-requirements/cash-differences.md` | Aprobado |
| Jornada del cobrador | `docs/01-requirements/collector-workday.md` | Aprobado |
| UC-24 — Registrar fondo entregado al cobrador | `docs/02-use-cases/UC-24-fondo-a-cobrador.md` | Aprobado |
| UC-25 — Operar caja central | `docs/02-use-cases/UC-25-caja-central.md` | Aprobado |
| UC-26 — Castigar crédito | `docs/02-use-cases/UC-26-castigar-credito.md` | Aprobado |
| Catálogo de entidades | `docs/04-database/catalogo-entidades.md` | Aprobado |
| Catálogo de estados | `docs/04-database/catalogo-estados.md` | Aprobado |
| Relaciones conceptuales | `docs/04-database/relaciones-conceptuales.md` | Aprobado |
| Modelo de movimientos financieros | `docs/04-database/modelo-movimientos-financieros.md` | Aprobado |
| Riesgos y decisiones abiertas | `docs/04-database/riesgos-y-decisiones-abiertas.md` | Aprobado |

## Documentos en construcción

| Entregable | Ruta prevista | Estado |
|---|---|---|
| Diagrama entidad-relación | `docs/04-database/` | En construcción |
| Diccionario de datos | `docs/04-database/` | En construcción |
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
