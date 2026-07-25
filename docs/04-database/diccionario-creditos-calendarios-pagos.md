# CREDIMEX — Diccionario: créditos, calendarios y pagos

**Estado:** Aprobado (Fase 3A.2)
**Dominios:** 4–7
**Alcance:** Columnas conceptuales. Sin tipos SQL.
**Fuentes:** Inventario lógico, D-54 a D-68, catálogo de entidades, D-01 a D-53.

Convenciones: ver `diccionario-identidad-clientes-rutas.md`.

---

## 1. `planes_credito`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Nombre | `nombre` | Etiqueta plan | texto corto | sí | capturado | mutable | — | D-01 | — |
| Estado | `estado` | Activo / inactivo | código de catálogo | sí | capturado | mutable | ACTIVO | — | — |

## 2. `versiones_plan`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Plan | `plan_credito_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Plazo cuotas | `plazo_cuotas` | Número de cuotas | número entero | sí | capturado | inmutable | — | append-only | — |
| Tasa | `tasa` | Tasa aplicable | porcentaje exacto | sí | capturado | inmutable | — | — | financiera |
| Vigente desde | `vigente_desde` | Inicio | fecha y hora | sí | capturado | inmutable | — | no afecta créditos previos | — |

## 3. `solicitudes_credito`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | Titular | identificador | sí | capturado | inmutable | — | — | — |
| Versión plan | `version_plan_id` | Plan snapshot | identificador | sí | capturado | inmutable | — | — | — |
| Monto solicitado | `monto_centavos` | Principal | importe en centavos | sí | capturado | mutable* | — | > 0; *hasta enviar | financiera |
| Estado | `estado` | Máquina solicitud | código de catálogo | sí | capturado | mutable | BORRADOR | sin DESEMBOLSADA | — |
| Crédito creado | `credito_id` | Crédito si APROBADA | identificador | no | generado | inmutable | — | 0..1 | — |
| Solicitante | `solicitante_id` | Usuario | identificador | sí | capturado | inmutable | — | — | auditoría |

## 4. `autorizaciones_credito`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-58 | — |
| Solicitud | `solicitud_credito_id` | FK exclusiva | identificador | sí | capturado | inmutable | — | solo solicitudes | — |
| Autorizador | `autorizador_id` | Usuario | identificador | sí | capturado | inmutable | — | D-12 | auditoría |
| Resultado | `resultado` | APROBADA / RECHAZADA / DEVUELTA | código de catálogo | sí | capturado | inmutable | — | append-only | — |
| Monto autorizado | `monto_centavos` | Monto | importe en centavos | no | capturado | inmutable | — | — | financiera |
| Observaciones | `observaciones` | Motivo | texto largo | no | capturado | inmutable | — | — | auditoría |
| Decidido en | `decidido_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | — | auditoría |

## 5. `creditos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | Titular | identificador | sí | capturado | inmutable | — | máx. 5 ACTIVOS con saldo > 0 | — |
| Solicitud origen | `solicitud_credito_id` | Origen | identificador | sí | capturado | inmutable | — | — | — |
| Estado | `estado` | Principal | código de catálogo | sí | capturado | mutable | PENDIENTE_DESEMBOLSO | D-21, D-23 | — |
| Saldo inicial | `saldo_inicial_centavos` | Monto + interés | importe en centavos | sí | generado | inmutable | — | D-10 | financiera |
| Saldo actual | `saldo_actual_centavos` | Proyección | importe en centavos | sí | derivado | proyectado | 0 | solo vía MovimientoCuenta | financiera |
| Días atraso | `dias_atraso_actual` | Caché atraso | número entero | sí | derivado | proyectado | 0 | D-28 | — |
| Semáforo | `semaforo_actual` | Semáforo | código de catálogo | sí | derivado | proyectado | — | D-28 | — |
| Cuotas vencidas | `cuotas_vencidas_pendientes` | Caché | número entero | sí | derivado | proyectado | 0 | D-28 | — |
| Fecha cálculo atraso | `fecha_calculo_atraso` | Último recálculo | fecha | no | derivado | proyectado | — | D-37 | — |
| Versión condiciones vigente | `version_condiciones_vigente_id` | Snapshot vigente | identificador | no | derivado | proyectado | — | — | — |

## 6. `renovaciones`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Crédito origen | `credito_origen_id` | Liquidado | identificador | sí | capturado | inmutable | — | UK origen; RN-REN | — |
| Crédito destino | `credito_destino_id` | Nuevo | identificador | sí | capturado | inmutable | — | UK destino | — |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 7. `versiones_condiciones_credito`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | 1:N | — |
| Monto | `monto_centavos` | Principal | importe en centavos | sí | capturado | inmutable | — | > 0 | financiera |
| Plazo cuotas | `plazo_cuotas` | Plazo | número entero | sí | capturado | inmutable | — | — | — |
| Tasa | `tasa` | Tasa | porcentaje exacto | sí | capturado | inmutable | — | — | financiera |
| Interés | `interes_centavos` | Interés | importe en centavos | sí | generado | inmutable | — | — | financiera |
| Total a pagar | `total_a_pagar_centavos` | Monto + interés | importe en centavos | sí | generado | inmutable | — | = saldo inicial | financiera |
| Cuota base | `cuota_base_centavos` | Cuota | importe en centavos | sí | generado | inmutable | — | D-05..D-08 | financiera |
| Comisión | `comision_centavos` | Comisión | importe en centavos | sí | generado | inmutable | — | fuera del saldo | financiera |
| Origen | `origen` | AUTORIZACION / REESTRUCTURA | código de catálogo | sí | capturado | inmutable | — | D-10, D-44 | — |
| Creado en | `creado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 8. `calendarios`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | máx. 1 VIGENTE | — |
| Estado | `estado` | VIGENTE / REEMPLAZADO | código de catálogo | sí | capturado | mutable | VIGENTE | — | — |
| Versión condiciones | `version_condiciones_id` | Snapshot | identificador | sí | capturado | inmutable | — | — | — |
| Fecha inicio | `fecha_inicio` | Inicio | fecha | sí | capturado | inmutable | — | — | — |
| Fecha final | `fecha_final` | Fin | fecha | sí | generado | mutable* | — | *ajuste festivos futuros D-53 | — |

## 9. `cuotas_programadas`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Calendario | `calendario_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Número | `numero` | Orden | número entero | sí | generado | inmutable | — | sin domingos/festivos | — |
| Fecha programada | `fecha_programada` | Fecha | fecha | sí | generado | mutable* | — | *solo PROGRAMADA + festivo futuro | — |
| Importe | `importe_centavos` | Importe fijo | importe en centavos | sí | generado | inmutable | — | cuota final ajustada | financiera |
| Estado | `estado` | Máquina cuota | código de catálogo | sí | derivado | mutable | PROGRAMADA | D-43 | — |
| Cubierto | `importe_cubierto_centavos` | Suma aplicaciones | importe en centavos | sí | derivado | proyectado | 0 | — | financiera |
| Es cuota final | `es_cuota_final` | Ajuste exacto | booleano | sí | generado | inmutable | false | D-08 | — |

## 10. `aplicaciones_pago_cuota`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Pago | `pago_id` | FK | identificador | sí | capturado | inmutable | — | N:M | financiera |
| Cuota | `cuota_programada_id` | FK | identificador | sí | capturado | inmutable | — | FIFO atraso | financiera |
| Importe aplicado | `importe_aplicado_centavos` | Cobertura | importe en centavos | sí | generado | inmutable | — | > 0; append-only | financiera |

## 11. `pagos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Operación | `operacion_financiera_id` | Folio | identificador | sí | generado | inmutable | — | PAGO o PRIMER_PAGO_RETENIDO | financiera |
| Importe | `importe_centavos` | Abono | importe en centavos | sí | capturado | inmutable | — | > 0 | financiera |
| Medio | `medio` | EFECTIVO / TRANSFERENCIA / RETENIDO_DESEMBOLSO | código de catálogo | sí | capturado | inmutable | — | D-49 | — |
| Folio ticket | `folio` | Folio | texto corto | sí | generado | inmutable | — | — | — |
| Saldo anterior | `saldo_anterior_centavos` | Snapshot | importe en centavos | sí | generado | inmutable | — | — | financiera |
| Saldo nuevo | `saldo_nuevo_centavos` | Snapshot | importe en centavos | sí | generado | inmutable | — | — | financiera |
| Estado | `estado` | CONFIRMADO / REVERTIDO | código de catálogo | sí | capturado | mutable | CONFIRMADO | sin borrado | — |
| Fecha operativa | `fecha_operativa` | Día | fecha | sí | capturado | inmutable | — | — | — |
| Cobrador | `cobrador_id` | Usuario | identificador | no | capturado | inmutable | — | si efectivo | — |

## 12. `reversos_pago`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Pago | `pago_id` | Pago original | identificador | sí | capturado | inmutable | — | 1:1; UK | — |
| Operación | `operacion_financiera_id` | REVERSO_PAGO | identificador | sí | generado | inmutable | — | refiere operación original | financiera |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | no para cuadrar caja | auditoría |
| Usuario | `usuario_id` | Supervisor/admin | identificador | sí | capturado | inmutable | — | — | auditoría |
| Revertido en | `revertido_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 13. `cuentas_bancarias`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-63 | — |
| Banco | `banco` | Nombre banco | texto corto | sí | capturado | mutable | — | — | — |
| Referencia / CLABE | `referencia` | Identificador cuenta | texto corto | sí | capturado | mutable | — | — | sensible |
| Uso | `uso` | RECEPCION_PAGOS / TESORERIA / AMBOS | código de catálogo | sí | capturado | mutable | — | D-63 | — |
| Estado | `estado` | Activa / inactiva | código de catálogo | sí | capturado | mutable | ACTIVA | — | — |
| Nombre | `nombre` | Etiqueta interna | texto corto | sí | capturado | mutable | — | no es ContraparteExterna | — |

## 14. `transferencias_bancarias`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Cuenta bancaria | `cuenta_bancaria_id` | Cuenta propia | identificador | sí | capturado | inmutable | — | D-63 | — |
| Pago | `pago_id` | Pago asociado | identificador | sí | generado | inmutable | — | — | financiera |
| Operación | `operacion_financiera_id` | Folio | identificador | sí | generado | inmutable | — | — | financiera |
| Referencia bancaria | `referencia_bancaria` | Ref. externa | texto corto | sí | capturado | inmutable | — | — | — |
| Importe | `importe_centavos` | Importe | importe en centavos | sí | capturado | inmutable | — | > 0; no mueve EFECTIVO_COBRADOR | financiera |
| Validador | `validador_id` | Usuario | identificador | sí | capturado | inmutable | — | — | auditoría |
| Estado | `estado` | REGISTRADA / ANULADA_POR_REVERSO | código de catálogo | sí | capturado | mutable | REGISTRADA | — | — |

## 15. `tickets`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Pago | `pago_id` | FK | identificador | sí | capturado | inmutable | — | solo tras confirmación servidor | — |
| Folio | `folio` | Folio ticket | texto corto | sí | generado | inmutable | — | RN-TIC | — |
| Datos snapshot | `datos_snapshot` | Datos RN-TIC | texto largo | sí | generado | inmutable | — | — | — |
| Medio | `medio` | Medio cobro | código de catálogo | sí | generado | inmutable | — | D-45 si retenido | — |
| Desembolso relacionado | `desembolso_id` | Si retenido | identificador | no | capturado | inmutable | — | D-45 | — |
| Leyenda retenido | `leyenda_primer_pago_retenido` | Marca | booleano | sí | generado | inmutable | false | D-45 | — |
| Estado impresión | `estado_impresion` | Estado | código de catálogo | sí | capturado | mutable | PENDIENTE | — | — |

## 16. `reimpresiones_ticket`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Ticket | `ticket_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Usuario | `usuario_id` | Quien reimprime | identificador | sí | capturado | inmutable | — | — | auditoría |
| Reimpreso en | `reimpreso_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | leyenda REIMPRESIÓN | auditoría |

## 17. `desembolsos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | ≤1 CONFIRMADO | — |
| Modalidad | `modalidad` | Modalidad entrega | código de catálogo | sí | capturado | inmutable | — | — | — |
| Principal | `principal_centavos` | Monto | importe en centavos | sí | capturado | inmutable | — | D-35 | financiera |
| Comisión | `comision_centavos` | Comisión | importe en centavos | sí | capturado | inmutable | — | fuera saldo | financiera |
| Primer pago retenido | `primer_pago_retenido_centavos` | Retención | importe en centavos | sí | capturado | inmutable | — | ≥ 0 | financiera |
| Efectivo neto | `efectivo_neto_centavos` | Entrega neta | importe en centavos | sí | derivado | inmutable | — | — | financiera |
| Estado | `estado` | Máquina desembolso | código de catálogo | sí | capturado | mutable | — | D-31 | — |
| Operación desembolso | `operacion_desembolso_id` | DESEMBOLSO_CREDITO | identificador | no | generado | inmutable | — | al confirmar | financiera |
| Operación retenido | `operacion_retenido_id` | PRIMER_PAGO_RETENIDO hija | identificador | no | generado | inmutable | — | D-36 | financiera |
| Cobrador | `cobrador_id` | Quien entrega | identificador | sí | capturado | inmutable | — | — | — |

## 18. `comisiones`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-61 | — |
| Desembolso | `desembolso_id` | FK única | identificador | sí | capturado | inmutable | — | UK; 1:0..1 | financiera |
| Importe | `importe_centavos` | Comisión liquidada | importe en centavos | sí | capturado | inmutable | — | D-11; > 0 | financiera |
| Modalidad | `modalidad` | Medio liquidación | código de catálogo | sí | capturado | inmutable | — | — | — |
| Liquidada en | `liquidada_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | solo si CONFIRMADO | — |

## 19. `reservas_efectivo`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-26 | — |
| Desembolso | `desembolso_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Cobrador | `cobrador_id` | Usuario | identificador | sí | capturado | inmutable | — | — | — |
| Importe | `importe_centavos` | Reservado | importe en centavos | sí | capturado | inmutable | — | > 0; no MovimientoCuenta | financiera |
| Estado | `estado` | ACTIVA / LIBERADA / CONSUMIDA | código de catálogo | sí | capturado | mutable | ACTIVA | — | — |

## 20. `reestructuraciones`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-44 | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Operación | `operacion_financiera_id` | REESTRUCTURACION_CREDITO | identificador | sí | generado | inmutable | — | 1:1 | financiera |
| Saldo base | `saldo_base_centavos` | Pendiente | importe en centavos | sí | capturado | inmutable | — | — | financiera |
| Nuevo plazo | `nuevo_plazo_cuotas` | Plazo | número entero | sí | capturado | inmutable | — | — | — |
| Nueva tasa | `nueva_tasa` | Tasa | porcentaje exacto | sí | capturado | inmutable | — | — | financiera |
| Nuevo interés | `nuevo_interes_centavos` | Interés cargado | importe en centavos | sí | generado | inmutable | — | mueve SALDO_CREDITO | financiera |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Autorizador | `autorizador_id` | Usuario | identificador | sí | capturado | inmutable | — | no en autorizaciones_credito | auditoría |
| Nueva versión condiciones | `version_condiciones_id` | Snapshot | identificador | sí | generado | inmutable | — | — | — |
| Nuevo calendario | `calendario_id` | Calendario VIGENTE | identificador | sí | generado | inmutable | — | — | — |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

---

## Referencias

- `docs/04-database/diccionario-cobranza-caja-entregas.md`
- `docs/04-database/diccionario-libro-auditoria-procesos.md`
