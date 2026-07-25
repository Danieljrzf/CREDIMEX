# CREDIMEX — Claves, relaciones y restricciones lógicas

**Estado:** Aprobado (Fase 3A.2)
**Fecha:** 25 de julio de 2026
**Alcance:** Modelo lógico. Sin SQL.
**Fuentes:** Inventario lógico, diccionarios, D-01 a D-68.

## Clasificación de reglas

| Código | Significado |
|---|---|
| **BD** | Candidata a restricción de base de datos (PK, FK, UNIQUE, CHECK simple) |
| **APP** | Validación de aplicación |
| **TX** | Regla transaccional (misma unidad de trabajo atómica) |
| **REC** | Proceso de reconciliación / auditoría posterior |

---

## 1. Claves primarias conceptuales

Todas las tablas del inventario tienen PK conceptual `id` (identificador
generado, inmutable).

| Clasificación | Regla |
|---|---|
| BD | Cada tabla candidata tiene clave primaria `id`. |

---

## 2. Claves foráneas principales

### 2.1 Identidad

| Desde | Hacia | Cardinalidad | Clasif. |
|---|---|---|---|
| `usuarios.rol_id` | `roles` | N:1 | BD |
| `rol_permisos.rol_id` | `roles` | N:1 | BD |
| `rol_permisos.permiso_id` | `permisos` | N:1 | BD |
| `dispositivos.usuario_id` | `usuarios` | N:1 | BD |
| `sesiones_token.usuario_id` | `usuarios` | N:1 | BD |
| `sesiones_token.dispositivo_id` | `dispositivos` | N:1 opcional | BD |

### 2.2 Clientes y rutas

| Desde | Hacia | Cardinalidad | Clasif. |
|---|---|---|---|
| `contactos_alternativos.cliente_id` | `clientes` | N:1 | BD |
| `referencias.cliente_id` | `clientes` | N:1 | BD |
| `domicilios_ubicaciones.cliente_id` | `clientes` | N:1 | BD |
| `documentos_cliente.cliente_id` | `clientes` | N:1 | BD |
| `confirmaciones_no_duplicado.cliente_id` | `clientes` | N:1 | BD |
| `asignaciones_ruta_cobrador.ruta_id` | `rutas` | N:1 | BD |
| `asignaciones_ruta_cobrador.cobrador_id` | `usuarios` | N:1 | BD |
| `asignaciones_cliente_ruta.cliente_id` | `clientes` | N:1 | BD |
| `asignaciones_cliente_ruta.ruta_id` | `rutas` | N:1 | BD |
| `asignaciones_temporales_cobranza.cobrador_id` | `usuarios` | N:1 | BD |
| `asignaciones_temporales_rutas.asignacion_temporal_id` | `asignaciones_temporales_cobranza` | N:1 | BD |
| `asignaciones_temporales_rutas.ruta_id` | `rutas` | N:1 | BD |
| `asignaciones_temporales_clientes.asignacion_temporal_id` | `asignaciones_temporales_cobranza` | N:1 | BD |
| `asignaciones_temporales_clientes.cliente_id` | `clientes` | N:1 | BD |
| `asignaciones_temporales_creditos.asignacion_temporal_id` | `asignaciones_temporales_cobranza` | N:1 | BD |
| `asignaciones_temporales_creditos.credito_id` | `creditos` | N:1 | BD |

### 2.3 Créditos, calendarios y pagos

| Desde | Hacia | Cardinalidad | Clasif. |
|---|---|---|---|
| `versiones_plan.plan_credito_id` | `planes_credito` | N:1 | BD |
| `solicitudes_credito.cliente_id` | `clientes` | N:1 | BD |
| `solicitudes_credito.version_plan_id` | `versiones_plan` | N:1 | BD |
| `solicitudes_credito.credito_id` | `creditos` | 0..1 | BD |
| `autorizaciones_credito.solicitud_credito_id` | `solicitudes_credito` | N:1 | BD (D-58) |
| `creditos.cliente_id` | `clientes` | N:1 | BD |
| `creditos.solicitud_credito_id` | `solicitudes_credito` | N:1 | BD |
| `renovaciones.credito_origen_id` | `creditos` | 1:1 | BD |
| `renovaciones.credito_destino_id` | `creditos` | 1:1 | BD |
| `versiones_condiciones_credito.credito_id` | `creditos` | N:1 | BD |
| `calendarios.credito_id` | `creditos` | N:1 | BD |
| `cuotas_programadas.calendario_id` | `calendarios` | N:1 | BD |
| `aplicaciones_pago_cuota.pago_id` | `pagos` | N:1 | BD |
| `aplicaciones_pago_cuota.cuota_programada_id` | `cuotas_programadas` | N:1 | BD |
| `pagos.credito_id` | `creditos` | N:1 | BD |
| `pagos.operacion_financiera_id` | `operaciones_financieras` | N:1 | BD |
| `reversos_pago.pago_id` | `pagos` | 1:1 | BD |
| `reversos_pago.operacion_financiera_id` | `operaciones_financieras` | N:1 | BD |
| `transferencias_bancarias.cuenta_bancaria_id` | `cuentas_bancarias` | N:1 | BD (D-63) |
| `transferencias_bancarias.pago_id` | `pagos` | N:1 | BD |
| `tickets.pago_id` | `pagos` | N:1 | BD |
| `reimpresiones_ticket.ticket_id` | `tickets` | N:1 | BD |
| `desembolsos.credito_id` | `creditos` | N:1 | BD |
| `comisiones.desembolso_id` | `desembolsos` | 1:0..1 | BD (D-61) |
| `reservas_efectivo.desembolso_id` | `desembolsos` | N:1 | BD |
| `reestructuraciones.credito_id` | `creditos` | N:1 | BD |
| `reestructuraciones.operacion_financiera_id` | `operaciones_financieras` | 1:1 | BD |

### 2.4 Caja, entregas e incidencias

| Desde | Hacia | Cardinalidad | Clasif. |
|---|---|---|---|
| `jornadas_cobrador.cobrador_id` | `usuarios` | N:1 | BD |
| `jornadas_caja_central.caja_central_id` | `cajas_centrales` | N:1 | BD |
| `cortes_cobrador.jornada_cobrador_id` | `jornadas_cobrador` | N:1 | BD |
| `cortes_caja_central.jornada_caja_central_id` | `jornadas_caja_central` | N:1 | BD |
| `entregas_efectivo.motivo_entrega_fondo_id` | `motivos_entrega_fondo` | N:1 opcional | BD |
| `gastos_ruta.operacion_financiera_id` | `operaciones_financieras` | 1:1 | BD (D-66) |
| `gastos_ruta.jornada_cobrador_id` | `jornadas_cobrador` | N:1 | BD |
| `operaciones_tesoreria.operacion_financiera_id` | `operaciones_financieras` | 1:0..1 | BD (D-64) |
| `resoluciones_incidencia_caja.incidencia_caja_id` | `incidencias_caja` | N:1 | BD (D-62) |
| `resoluciones_incidencia_caja.operacion_financiera_id` | `operaciones_financieras` | 1:0..1 | BD (D-62) |
| `castigos_credito.credito_id` | `creditos` | 1:1 | BD |
| `recuperaciones_credito_castigado.credito_id` | `creditos` | N:1 | BD |
| `recuperaciones_credito_castigado.operacion_financiera_id` | `operaciones_financieras` | 1:1 | BD |

### 2.5 Libro, auditoría y procesos

| Desde | Hacia | Cardinalidad | Clasif. |
|---|---|---|---|
| `movimientos_cuenta.operacion_financiera_id` | `operaciones_financieras` | N:1 | BD |
| `movimientos_cuenta.cuenta_operativa_id` | `cuentas_operativas` | N:1 | BD |
| `operaciones_financieras.operacion_padre_id` | `operaciones_financieras` | N:1 opcional | BD |
| `operaciones_financieras.lote_carga_inicial_id` | `lotes_carga_inicial` | N:1 opcional | BD (D-56) |
| `idempotencias_operacion.operacion_financiera_id` | `operaciones_financieras` | N:1 opcional | BD (D-55) |
| `evidencias_operacion.operacion_financiera_id` | `operaciones_financieras` | N:1 | BD |
| `eventos_auditoria.operacion_financiera_id` | `operaciones_financieras` | N:1 opcional | BD (D-60) |
| `cuentas_operativas.usuario_cobrador_id` | `usuarios` | 0..1 | BD (D-59) |
| `cuentas_operativas.caja_central_id` | `cajas_centrales` | 0..1 | BD (D-59) |
| `cuentas_operativas.cuenta_bancaria_id` | `cuentas_bancarias` | 0..1 | BD (D-59) |
| `cuentas_operativas.credito_id` | `creditos` | 0..1 | BD (D-59) |
| `contrapartes_externas.cliente_id` | `clientes` | 0..1 | BD |

`eventos_auditoria.entidad_tipo` + `entidad_id` **no** son FK físicas (D-60).

---

## 3. Relaciones 1:1, 1:N y N:M

### 3.1 Uno a uno

| Relación | Nota | Clasif. |
|---|---|---|
| Usuario cobrador ↔ `cuentas_operativas` (`EFECTIVO_COBRADOR`) | Un activo por cobrador | BD + APP |
| `cajas_centrales` ↔ `cuentas_operativas` (`CAJA_CENTRAL`) | Un por caja | BD |
| `cuentas_bancarias` ↔ `cuentas_operativas` (`CUENTA_BANCARIA`) | Un por cuenta | BD |
| `creditos` ↔ `cuentas_operativas` (`SALDO_CREDITO`) | Un por crédito | BD |
| `desembolsos` ↔ `comisiones` | 0..1; obligatorio si CONFIRMADO | BD + APP (D-61) |
| `pagos` ↔ `reversos_pago` | 0..1 | BD |
| `creditos` ↔ `castigos_credito` | 0..1 | BD |
| `OperacionFinanciera` ↔ `operaciones_tesoreria` | 0..1; obligatorio en 6 tipos | BD + APP (D-64) |
| `OperacionFinanciera` ↔ `gastos_ruta` | 0..1; obligatorio si `GASTO_RUTA` | BD + APP (D-66) |
| `OperacionFinanciera` ↔ `resoluciones_incidencia_caja` | 0..1 | BD (D-62) |

### 3.2 Uno a muchos

| Relación | Clasif. |
|---|---|
| `roles` 1:N `usuarios` | BD (D-27) |
| `clientes` 1:N `creditos` | BD |
| `solicitudes_credito` 1:N `autorizaciones_credito` | BD (D-58) |
| `creditos` 1:N `desembolsos` | BD |
| `creditos` 1:N `versiones_condiciones_credito` | BD |
| `creditos` 1:N `calendarios` | BD |
| `calendarios` 1:N `cuotas_programadas` | BD |
| `creditos` 1:N `pagos` | BD |
| `lotes_carga_inicial` 1:N `operaciones_financieras` | BD (D-56) |
| `incidencias_caja` 1:N `resoluciones_incidencia_caja` | BD (D-62) |
| `asignaciones_temporales_cobranza` 1:N detalle tipado | BD (D-57, D-67) |
| `operaciones_financieras` 1:N `movimientos_cuenta` | BD |
| `jornadas_cobrador` 1:N `gastos_ruta` | BD (D-66) |

### 3.3 Muchos a muchos (tablas intermedias)

| Enlace | Une | Clasif. |
|---|---|---|
| `rol_permisos` | Rol ↔ Permiso | BD |
| `aplicaciones_pago_cuota` | Pago ↔ CuotaProgramada | BD |

### 3.4 Autorrelaciones

| Relación | Nota | Clasif. |
|---|---|---|
| `operaciones_financieras.operacion_padre_id` | Padre/hija (retenido, reverso, etc.) | BD |
| `renovaciones` | Crédito origen → crédito destino | BD |

---

## 4. Restricciones únicas (candidatas BD)

| UK conceptual | Tabla | Clasif. | Origen |
|---|---|---|---|
| (`cobrador_id`, `fecha_operativa`) | `jornadas_cobrador` | BD | D-22 |
| (`caja_central_id`, `fecha_operativa`) | `jornadas_caja_central` | BD | D-40 |
| `folio` | `operaciones_financieras` | BD | libro |
| (`ambito`, `clave`) | `idempotencias_operacion` | BD | D-55, D-68 |
| `desembolso_id` | `comisiones` | BD | D-61 |
| `operacion_financiera_id` | `operaciones_tesoreria` | BD | D-64 |
| `operacion_financiera_id` | `gastos_ruta` | BD | D-66 |
| `operacion_financiera_id` | `resoluciones_incidencia_caja` | BD | D-62 |
| `pago_id` | `reversos_pago` | BD | — |
| `credito_id` | `castigos_credito` | BD | — |
| `credito_origen_id` / `credito_destino_id` | `renovaciones` | BD | — |
| `codigo` | `cajas_centrales`, `roles`, `permisos`, `motivos_entrega_fondo` | BD | — |
| `referencia_lote` | `lotes_carga_inicial` | BD | D-56 |
| `fecha` | `dias_festivos` | BD | — |
| (`asignacion_temporal_id`, `ruta_id`) | `asignaciones_temporales_rutas` | BD | D-67 |
| (`asignacion_temporal_id`, `cliente_id`) | `asignaciones_temporales_clientes` | BD | D-67 |
| (`asignacion_temporal_id`, `credito_id`) | `asignaciones_temporales_creditos` | BD | D-67 |
| (`lote_carga_inicial_id`, `cuenta_operativa`) vía OF+movimientos | carga inicial | APP + BD parcial | D-56 |
| Un `EFECTIVO_COBRADOR` activo por cobrador | `cuentas_operativas` | BD parcial | D-59 |
| Un `SALDO_CREDITO` por crédito | `cuentas_operativas` | BD | D-59 |
| Un `CAJA_CENTRAL` por caja | `cuentas_operativas` | BD | D-59 |
| Un `CUENTA_BANCARIA` por cuenta bancaria | `cuentas_operativas` | BD | D-59 |
| Máximo una `CajaCentral` `ACTIVA` en V1 | `cajas_centrales` | BD parcial / APP | D-39 |

---

## 5. Validaciones condicionales (APP / BD parcial)

| Regla | Clasif. | Origen |
|---|---|---|
| Exactamente una FK de dueño en `cuentas_operativas` y tipo ↔ dueño | BD CHECK + APP | D-59 |
| Máximo un calendario `VIGENTE` por crédito | BD parcial + APP | D-21 / relaciones |
| Máximo un desembolso `CONFIRMADO` por crédito | BD parcial + APP | D-31 |
| Desembolso `CONFIRMADO` ⇒ exactamente una `comision` | APP + TX | D-61 |
| Desembolso cancelado ⇒ sin comisión | APP | D-61 |
| Tipos de tesorería (6) ⇒ exactamente una `operaciones_tesoreria` | APP + TX | D-64 |
| Ningún otro tipo usa `operaciones_tesoreria` | APP | D-64 |
| `GASTO_RUTA` ⇒ exactamente una `gastos_ruta` | APP + TX | D-66 |
| Cabecera temporal: `tipo_alcance` determina tabla de detalle | APP + TX | D-67 |
| Cabecera temporal con ≥1 detalle; sin cabeceras vacías | APP + TX | D-67 |
| Un crédito operable por un solo cobrador a la vez | APP | D-67, reglas rutas |
| `autorizaciones_credito` solo con `solicitudes_credito` | BD | D-58 |
| `contrapartes_externas.cliente_id` obligatorio si tipo `CLIENTE` | APP / BD CHECK | D-48 |
| `evidencias_operacion.archivo_privado` si `CON_DOCUMENTO` | APP | D-41 |
| Importes de dominio > 0 donde aplique | APP / BD CHECK | reglas financieras |
| Resolución no supera `importe_pendiente` | APP + TX | D-65 |
| Incidencia `RESUELTA` solo si pendiente = 0 y cierre explícito | APP | D-65 |
| Cierre con ajuste de efectivo solo administrador | APP | D-65, D-52 |
| Misma clave idempotencia + huella distinta → error | APP | D-68 |
| Misma clave + misma huella → resultado anterior | APP + TX | D-68 |
| Subtipo tesorería en huella de `REGISTRAR_OPERACION_TESORERIA` | APP | D-68 |
| Máximo cinco créditos `ACTIVO` con saldo > 0 por cliente | APP | D-21 / RN |
| Cliente restringido: no nuevos créditos / renovaciones / reestructura con dinero nuevo | APP | RN-RES |
| `CANCELADO` de crédito solo desde `PENDIENTE_DESEMBOLSO` | APP | D-23 |
| `CARGA_INICIAL_SALDO` bloqueada tras primer cierre de puesta en marcha | APP | D-42, D-51 |
| Toda `CARGA_INICIAL_SALDO` pertenece a un lote | APP + BD | D-56 |
| Cuenta propia ≠ `ContraparteExterna` | APP | D-63 |
| Existencia de entidad en auditoría lógica | APP | D-60 |

---

## 6. Reglas transaccionales (TX)

| Regla | Clasif. | Origen |
|---|---|---|
| Toda escritura financiera en una transacción | TX | maestro / AGENTS |
| Crear/actualizar `MovimientoCuenta` y `saldo_actual` + `version` juntos | TX | D-21, D-32 |
| Saldo nunca se modifica sin `MovimientoCuenta` (salvo creación en cero) | TX + APP | D-21 |
| Operación tesorería + detalle + movimientos en la misma TX | TX | D-64 |
| `GASTO_RUTA` + `gastos_ruta` + movimientos en la misma TX | TX | D-66 |
| Cabecera temporal + detalles en la misma TX | TX | D-67 |
| Desembolso confirmado: OF + movimientos brutos + comisión + reserva consumida | TX | D-31, D-35, D-61 |
| Primer pago retenido como OF hija con folio e idempotencia propios | TX | D-36 |
| Pago + aplicaciones FIFO + ticket + proyección saldo/atraso | TX | RN-PAG, D-28 |
| Reverso: OF hija que referencia operación original + inversión de movimientos | TX | D-04 |
| Reestructura: nueva versión condiciones + calendario + cuotas canceladas + interés | TX | D-44 |
| Idempotencia: pasar a `COMPLETADA` con `operacion_financiera_id` al finalizar | TX | D-55 |
| Carga inicial: una OF por cuenta en el lote; evidencia obligatoria | TX | D-51, D-56 |

---

## 7. Procesos de reconciliación (REC)

| Proceso | Clasif. | Origen |
|---|---|---|
| Recálculo diario/manual de atraso y semáforo (`ejecuciones_proceso_atraso`) | REC | D-37, D-28 |
| Reconciliar `saldo_actual_centavos` vs suma de `movimientos_cuenta` | REC | libro |
| Reconciliar `importe_resuelto` / `importe_pendiente` de incidencias vs resoluciones | REC | D-65 |
| Cuota final ajustada al total exacto del crédito | REC + APP | D-08 |
| Comisión fuera del saldo (verificar no incluida en `SALDO_CREDITO`) | REC | D-11 |
| Disponible cobrador = saldo − reservas `ACTIVAS` | REC + APP | D-26 |
| Detectar múltiples calendarios `VIGENTE` o desembolsos `CONFIRMADO` | REC | salvaguarda |
| Conciliación de lotes de carga inicial antes de `CERRADO` | REC | D-51 |
| Festivos futuros: recalcular solo cuotas `PROGRAMADA` | REC + APP | D-53 |

---

## 8. Eliminación lógica y prohibición de borrado físico

| Regla | Clasif. | Origen |
|---|---|---|
| No eliminar físicamente pagos, créditos, cortes ni movimientos financieros | APP + política BD | AGENTS / maestro |
| Correcciones financieras mediante reversos u OF de ajuste | APP + TX | D-04, D-30, D-52 |
| Tablas append-only: no UPDATE de hechos (salvo estado controlado documentado) | APP | catálogo entidades |
| Usuarios/contrapartes con movimientos: sin borrado físico | APP | D-27, D-34 |
| Documentos cliente: sustitución = nuevo registro | APP | expediente |

---

## 9. Resumen por decisión D-54 a D-68

| Decisión | Efecto en claves/restricciones |
|---|---|
| D-54 | Detalle único `operaciones_tesoreria`; `gastos_ruta` separado |
| D-55 | UK (`ambito`,`clave`); OF opcional |
| D-56 | Lote 1:N OF; UK cuenta por lote |
| D-57 / D-67 | Cabecera + 3 detalles; `tipo_alcance`; TX creación |
| D-58 | FK solo a `solicitudes_credito` |
| D-59 | Dueño exclusivo + unicidades por tipo |
| D-60 | Auditoría lógica + FK opcional a OF |
| D-61 | UK `comisiones.desembolso_id`; 1:0..1 |
| D-62 / D-65 | Resoluciones 1:N; cierre explícito; parciales |
| D-63 | `cuentas_bancarias` propias |
| D-64 | Detalle obligatorio en 6 tipos; misma TX |
| D-66 | `GASTO_RUTA` ↔ `gastos_ruta` 1:1 |
| D-68 | Catálogo de ámbitos; huella |

---

## Referencias

- `docs/04-database/inventario-tablas-logicas.md`
- `docs/04-database/relaciones-conceptuales.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`
