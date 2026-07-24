# CREDIMEX — Historial de documentación

## Propósito

Este archivo registra las decisiones, correcciones, versiones y efectos sobre
los futuros manuales de CREDIMEX. Es el registro cronológico oficial de la
evolución documental del proyecto.

## Convenciones

Cada registro debe contener:

- fecha;
- versión;
- fase;
- archivos creados;
- archivos modificados;
- decisiones agregadas;
- decisiones reemplazadas;
- pendientes;
- impacto en manuales;
- commit relacionado.

## Historial inicial

### Versión 1.2

- **Fecha:** 24 de julio de 2026
- **Fase:** Línea base funcional y técnica
- **Archivos creados:**
  - `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`
- **Archivos modificados:** ninguno
- **Decisiones agregadas:** línea base de requerimientos y reglas de negocio
- **Decisiones reemplazadas:** ninguna
- **Contenido:**
  - Documento maestro del producto y desarrollo.
  - Requerimientos.
  - Casos de uso.
  - Procesos.
  - Arquitectura y estrategia de trabajo con Cursor.
- **Pendientes:** ambigüedades resueltas posteriormente en v1.3 y v1.4
- **Impacto en manuales:** base funcional para todos los manuales futuros
- **Commit relacionado:** pendiente de registrar

### Versión 1.3

- **Fecha:** 24 de julio de 2026
- **Fase:** Normalización documental
- **Archivos creados:**
  - `docs/00-index.md`
  - `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md`
  - `docs/01-requirements/cash-differences.md`
  - `docs/01-requirements/collector-workday.md`
  - `docs/02-use-cases/UC-24-fondo-a-cobrador.md`
  - `docs/02-use-cases/UC-25-caja-central.md`
  - `docs/02-use-cases/UC-26-castigar-credito.md`
- **Archivos modificados:**
  - `AGENTS.md`
- **Decisiones agregadas:** D-01 a D-20
- **Decisiones reemplazadas:** ninguna (precisan redacciones ambiguas del
  maestro v1.2)
- **Contenido:**
  - Decisiones D-01 a D-20.
  - Nuevos casos de uso UC-24, UC-25 y UC-26.
  - Diferencias de caja.
  - Jornada del cobrador.
  - Normalización de rutas documentales.
- **Pendientes:** reflejar las decisiones en el documento maestro cuando se
  autorice su actualización
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual del cobrador;
  - Manual del supervisor;
  - Manual del administrador;
  - Manual de operación de caja;
  - Manual técnico;
  - Guía rápida;
  - Manual de incidencias.
- **Commit relacionado:** pendiente de registrar

### Versión 1.4

- **Fecha:** 24 de julio de 2026
- **Fase:** Fase 3A — Modelo conceptual de datos
- **Archivos creados:**
  - `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`
  - `docs/04-database/catalogo-entidades.md`
  - `docs/04-database/catalogo-estados.md`
  - `docs/04-database/relaciones-conceptuales.md`
  - `docs/04-database/modelo-movimientos-financieros.md`
  - `docs/04-database/riesgos-y-decisiones-abiertas.md`
- **Archivos modificados:**
  - `docs/00-index.md`
- **Decisiones agregadas:** D-21 a D-32
- **Decisiones reemplazadas:** ninguna (complementan D-01 a D-20)
- **Contenido:**
  - Decisiones D-21 a D-32.
  - Catálogo conceptual de entidades.
  - Catálogo de estados.
  - Relaciones conceptuales.
  - Libro operativo de movimientos.
  - Riesgos y decisiones abiertas.
- **Pendientes:** los listados en
  `docs/04-database/riesgos-y-decisiones-abiertas.md`
- **Impacto en manuales:** estas decisiones se utilizarán posteriormente para:
  - Manual del cobrador;
  - Manual del supervisor;
  - Manual del administrador;
  - Manual de operación de caja;
  - Manual técnico;
  - Guía rápida;
  - Manual de incidencias.
- **Commit relacionado:** `67e41fe` — docs: documentar el modelo conceptual y las decisiones de datos
