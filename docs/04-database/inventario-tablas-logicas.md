# CREDIMEX — Inventario de tablas lógicas

**Estado:** Aprobado (Fase 3A.2)
**Fecha:** 25 de julio de 2026
**Alcance:** Modelo lógico. Sin tipos SQL.
**Decisiones:** D-54 a D-68 (`CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`)
**Fuentes:** Catálogo de entidades, relaciones conceptuales, modelo de
movimientos, catálogo de operaciones, D-01 a D-53.

**Conteo final:** 67 tablas candidatas.

Leyenda de tipo:

| Código | Significado |
|---|---|
| C | Catálogo |
| T | Transaccional |
| H | Histórica / versionada |
| P | Proyección (caché reconciliable) |
| A | Append-only |

---

## 1. Identidad y seguridad (6)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Usuario | `usuarios` | Persona con acceso | Usuario | T |
| Rol | `roles` | Perfil de permisos V1 | Rol | C |
| Permiso | `permisos` | Capacidad atómica | Permiso | C |
| Rol–Permiso | `rol_permisos` | Asignación N:M | RolPermiso | T |
| Dispositivo | `dispositivos` | Vínculo de sesión al teléfono | Dispositivo | T |
| Sesión / token | `sesiones_token` | Autenticación API | SesionToken | T |

---

## 2. Clientes y expedientes (6)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Cliente | `clientes` | Titular de créditos | Cliente | T |
| Contacto alternativo | `contactos_alternativos` | Contacto obligatorio | ContactoAlternativo | H |
| Referencia | `referencias` | Referencias del cliente | Referencia | H |
| Domicilio / ubicación | `domicilios_ubicaciones` | Dirección y GPS | DomicilioUbicacion | H |
| Documento de cliente | `documentos_cliente` | INE / comprobante | DocumentoCliente | A |
| Confirmación no duplicado | `confirmaciones_no_duplicado` | Confirmación “otra persona” | ConfirmacionNoDuplicado | A |

---

## 3. Rutas y asignaciones (7)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Ruta | `rutas` | Zona de cobranza | Ruta | T |
| Asignación ruta–cobrador | `asignaciones_ruta_cobrador` | Titular histórico de ruta | AsignacionRutaCobrador | H |
| Asignación cliente–ruta | `asignaciones_cliente_ruta` | Pertenencia a ruta | AsignacionClienteRuta | H |
| Asignación temporal cobranza | `asignaciones_temporales_cobranza` | Cabecera de cobro temporal | AsignacionTemporalCobranza | H |
| Asignación temporal ruta | `asignaciones_temporales_rutas` | Detalle alcance RUTA | D-57 / D-67 | H |
| Asignación temporal cliente | `asignaciones_temporales_clientes` | Detalle alcance CLIENTE | D-57 / D-67 | H |
| Asignación temporal crédito | `asignaciones_temporales_creditos` | Detalle alcance CREDITO | D-57 / D-67 | H |

---

## 4. Solicitudes, créditos y autorizaciones (6)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Plan de crédito | `planes_credito` | Catálogo de plazos y tasas | PlanCredito | C |
| Versión de plan | `versiones_plan` | Snapshot de plan | VersionPlan | A |
| Solicitud de crédito | `solicitudes_credito` | Pedido antes del desembolso | SolicitudCredito | T |
| Autorización de crédito | `autorizaciones_credito` | Decisión sobre solicitud | Autorizacion (D-58) | A |
| Crédito | `creditos` | Préstamo y proyecciones | Credito | T+P |
| Renovación | `renovaciones` | Vínculo crédito liquidado → nuevo | Renovacion | A |

---

## 5. Condiciones, calendarios y cuotas (4)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Versión condiciones crédito | `versiones_condiciones_credito` | Condiciones definitivas | VersionCondicionesCredito | A |
| Calendario | `calendarios` | Versión de calendario | Calendario | H |
| Cuota programada | `cuotas_programadas` | Cuota exigible | CuotaProgramada | T |
| Aplicación pago–cuota | `aplicaciones_pago_cuota` | Cobertura FIFO | AplicacionPagoCuota | A |

---

## 6. Pagos, transferencias y tickets (6)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Pago | `pagos` | Abono al crédito | Pago | A |
| Reverso de pago | `reversos_pago` | Corrección de pago | ReversoPago | A |
| Transferencia bancaria | `transferencias_bancarias` | Pago verificado en cuenta | TransferenciaBancaria | A |
| Cuenta bancaria | `cuentas_bancarias` | Cuentas propias CREDIMEX | CuentaReceptora → D-63 | C/T |
| Ticket | `tickets` | Comprobante de pago | Ticket | A |
| Reimpresión de ticket | `reimpresiones_ticket` | Reimpresión marcada | ReimpresionTicket | A |

---

## 7. Desembolsos, comisiones y reestructuraciones (4)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Desembolso | `desembolsos` | Intento / entrega del préstamo | Desembolso | T |
| Comisión | `comisiones` | Comisión liquidada en desembolso | Comision (D-61) | A |
| Reserva de efectivo | `reservas_efectivo` | Disponible reservado | ReservaEfectivo | T |
| Reestructuración | `reestructuraciones` | Evento de cambio de condiciones | Reestructuracion | A |

---

## 8. Cobranza y visitas (2)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Visita sin pago | `visitas_sin_pago` | Evidencia de visita sin abono | VisitaSinPago | A |
| Promesa de pago | `promesas_pago` | Compromiso de pago | PromesaPago | T/H |

---

## 9. Jornadas y efectivo del cobrador (3)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Jornada cobrador | `jornadas_cobrador` | Día operativo del cobrador | JornadaCobrador | T |
| Excepción permanencia efectivo | `excepciones_permanencia_efectivo` | Autorización 4.ª noche | ExcepcionPermanenciaEfectivo | T |
| Corte cobrador | `cortes_cobrador` | Conciliación diaria cobrador | CorteCobrador | H |

---

## 10. Caja central (3)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Caja central | `cajas_centrales` | Contenedor de tesorería | CajaCentral | T |
| Jornada caja central | `jornadas_caja_central` | Día operativo de caja | JornadaCajaCentral | T |
| Corte caja central | `cortes_caja_central` | Conciliación diaria caja | CorteCajaCentral | H |

---

## 11. Entregas, cortes e incidencias (6)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Motivo entrega fondo | `motivos_entrega_fondo` | Catálogo motivo fondo | MotivoEntregaFondo | C |
| Entrega de efectivo | `entregas_efectivo` | Transferencia física doble confirmación | EntregaEfectivo | T |
| Gasto de ruta | `gastos_ruta` | Gasto autorizado del cobrador | Gasto → D-54 / D-66 | A |
| Operación de tesorería | `operaciones_tesoreria` | Detalle UC-25 (6 tipos) | D-54 / D-64 | A |
| Incidencia de caja | `incidencias_caja` | Faltante o sobrante | IncidenciaCaja | T |
| Resolución incidencia caja | `resoluciones_incidencia_caja` | Aplicación a incidencia | D-62 / D-65 | A |

---

## 12. Restricciones, castigos y recuperaciones (3)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Restricción cliente | `restricciones_cliente` | Bloqueo comercial | RestriccionCliente | T |
| Castigo de crédito | `castigos_credito` | Marca de castigo | CastigoCredito | A |
| Recuperación crédito castigado | `recuperaciones_credito_castigado` | Cobro post-castigo | RecuperacionCreditoCastigado | A |

---

## 13. Libro operativo (5)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Operación financiera | `operaciones_financieras` | Folio, tipo, idempotencia lógica | OperacionFinanciera | A |
| Cuenta operativa | `cuentas_operativas` | Saldo operativo proyectado | CuentaOperativa | T+P |
| Movimiento de cuenta | `movimientos_cuenta` | Asiento append-only | MovimientoCuenta | A |
| Idempotencia operación | `idempotencias_operacion` | Control de reintentos | IdempotenciaOperacion | A |
| Contraparte externa | `contrapartes_externas` | Actor externo de tesorería | ContraparteExterna | T |

---

## 14. Auditoría, evidencias y configuración (4)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Evento de auditoría | `eventos_auditoria` | Trazabilidad de cambios | EventoAuditoria | A |
| Evidencia de operación | `evidencias_operacion` | Sustento documental/textual | EvidenciaOperacion | A |
| Parámetro de sistema | `parametros_sistema` | Límites y configuración | ParametroSistema | H |
| Día festivo | `dias_festivos` | Día sin cuota programada | DiaFestivo | C/T |

---

## 15. Procesos automáticos y puesta en marcha (2)

| Nombre lógico | Técnico | Propósito | Entidad origen | Tipo |
|---|---|---|---|---|
| Ejecución proceso atraso | `ejecuciones_proceso_atraso` | Bitácora recálculo atraso | EjecucionProcesoAtraso | A |
| Lote carga inicial | `lotes_carga_inicial` | Agrupa CARGA_INICIAL_SALDO | D-56 | T |

---

## Cambios respecto al inventario preliminar

| Acción | Tablas |
|---|---|
| Eliminadas | `aportaciones`, `ingresos_extraordinarios`, `depositos_bancarios_manuales`, `retiros` |
| Fusionadas en | `operaciones_tesoreria` |
| Renombradas | `gastos` → `gastos_ruta`; `autorizaciones` → `autorizaciones_credito`; `cuentas_receptoras` → `cuentas_bancarias` |
| Nuevas | `operaciones_tesoreria`, `asignaciones_temporales_rutas`, `asignaciones_temporales_clientes`, `asignaciones_temporales_creditos`, `resoluciones_incidencia_caja`, `lotes_carga_inicial` |

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`
- `docs/04-database/diccionario-identidad-clientes-rutas.md`
- `docs/04-database/diccionario-creditos-calendarios-pagos.md`
- `docs/04-database/diccionario-cobranza-caja-entregas.md`
- `docs/04-database/diccionario-libro-auditoria-procesos.md`
