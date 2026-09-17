# CREDIMEX — Borrado, inactivación y retención

**Estado:** Aprobado (Fase 3A.3)
**Decisiones:** D-77, D-91.
**Inventario:** 67 tablas.
**Alcance:** Cada tabla aparece **una sola vez** en su categoría
principal. Las purgas técnicas son política secundaria.

**Término oficial:** Sin eliminación física ordinaria.

No se afirma retención permanente de PII sin revisión legal aprobada.

No hay `deleted_at` generalizado.

---

## 1. Sin eliminación física ordinaria (25)

Hechos financieros e históricos: no se borran mediante funciones
normales de la aplicación.

| # | Tabla |
|---|---|
| 1 | `documentos_cliente` |
| 2 | `confirmaciones_no_duplicado` |
| 3 | `versiones_plan` |
| 4 | `autorizaciones_credito` |
| 5 | `versiones_condiciones_credito` |
| 6 | `aplicaciones_pago_cuota` |
| 7 | `pagos` |
| 8 | `reversos_pago` |
| 9 | `transferencias_bancarias` |
| 10 | `tickets` |
| 11 | `reimpresiones_ticket` |
| 12 | `comisiones` |
| 13 | `reestructuraciones` |
| 14 | `renovaciones` |
| 15 | `visitas_sin_pago` |
| 16 | `gastos_ruta` |
| 17 | `operaciones_tesoreria` |
| 18 | `resoluciones_incidencia_caja` |
| 19 | `castigos_credito` |
| 20 | `recuperaciones_credito_castigado` |
| 21 | `operaciones_financieras` |
| 22 | `movimientos_cuenta` |
| 23 | `evidencias_operacion` |
| 24 | `eventos_auditoria` |
| 25 | `ejecuciones_proceso_atraso` |

**Política secundaria (D-91):** `documentos_cliente` y
`evidencias_operacion` podrán sujetarse a futura purga, anonimización o
bloqueo conforme a una política legal aprobada, conservando metadata
histórica sanitizada cuando corresponda. No constituye borrado ordinario.

---

## 2. Inactivación mediante estado (12)

| # | Tabla |
|---|---|
| 1 | `usuarios` |
| 2 | `roles` |
| 3 | `permisos` |
| 4 | `clientes` |
| 5 | `rutas` |
| 6 | `planes_credito` |
| 7 | `cuentas_bancarias` |
| 8 | `cajas_centrales` |
| 9 | `motivos_entrega_fondo` |
| 10 | `contrapartes_externas` |
| 11 | `parametros_sistema` |
| 12 | `dias_festivos` |

Mecanismos: `estado`, `activo` o versionado de vigencia (parámetros).
Para `roles` y `permisos`, v2.4 fija específicamente
`activo BOOLEAN NOT NULL DEFAULT true`.

---

## 3. Finalizar / reemplazar / revocar (29)

| # | Tabla |
|---|---|
| 1 | `contactos_alternativos` |
| 2 | `referencias` |
| 3 | `domicilios_ubicaciones` |
| 4 | `asignaciones_ruta_cobrador` |
| 5 | `asignaciones_cliente_ruta` |
| 6 | `asignaciones_temporales_cobranza` |
| 7 | `asignaciones_temporales_rutas` |
| 8 | `asignaciones_temporales_clientes` |
| 9 | `asignaciones_temporales_creditos` |
| 10 | `calendarios` |
| 11 | `cuotas_programadas` |
| 12 | `solicitudes_credito` |
| 13 | `creditos` |
| 14 | `desembolsos` |
| 15 | `reservas_efectivo` |
| 16 | `promesas_pago` |
| 17 | `jornadas_cobrador` |
| 18 | `jornadas_caja_central` |
| 19 | `excepciones_permanencia_efectivo` |
| 20 | `cortes_cobrador` |
| 21 | `cortes_caja_central` |
| 22 | `entregas_efectivo` |
| 23 | `incidencias_caja` |
| 24 | `restricciones_cliente` |
| 25 | `cuentas_operativas` |
| 26 | `idempotencias_operacion` |
| 27 | `lotes_carga_inicial` |
| 28 | `dispositivos` |
| 29 | `sesiones_token` |

Eventos de dominio candidatos (D-84): `confirmado_en`, `cerrado_en`,
`revocado_en`, `finalizado_en`, `reemplazado_en`, según entidad.

**Política secundaria:** `sesiones_token` e `idempotencias_operacion`
admiten purga técnica bajo retención aprobada (D-91).

---

## 4. Borrado físico limitado — categoría principal (1)

| # | Tabla | Nota |
|---|---|---|
| 1 | `rol_permisos` | Matriz mutable; filas eliminables de forma controlada; FK `NO ACTION` (v2.4) |

No implica CASCADE sobre hechos financieros.

---

## 5. Conteo

| Categoría principal | Cantidad |
|---|---|
| Sin eliminación física ordinaria | 25 |
| Inactivación mediante estado | 12 |
| Finalizar / reemplazar / revocar | 29 |
| Borrado físico limitado | 1 |
| **Total** | **67** |

---

## Referencias

- `docs/04-database/inventario-tablas-logicas.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
