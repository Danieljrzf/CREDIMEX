# CREDIMEX — Diccionario: cobranza, caja y entregas

**Estado:** Aprobado (Fase 3A.2)
**Dominios:** 8–12
**Alcance:** Columnas conceptuales. Sin tipos SQL.
**Fuentes:** Inventario lógico, D-54 a D-68, UC-24/25/26/27, cash-differences.

Convenciones: ver `diccionario-identidad-clientes-rutas.md`.

---

## 1. `visitas_sin_pago`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | UC-06 | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Cobrador | `cobrador_id` | Usuario | identificador | sí | capturado | inmutable | — | — | — |
| Motivo | `motivo` | Motivo | código de catálogo | sí | capturado | inmutable | — | — | — |
| Observaciones | `observaciones` | Texto | texto largo | no | capturado | inmutable | — | — | — |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 2. `promesas_pago`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | UC-06 | — |
| Visita | `visita_sin_pago_id` | Origen | identificador | no | capturado | inmutable | — | — | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Fecha prometida | `fecha_prometida` | Compromiso | fecha | sí | capturado | mutable | — | — | — |
| Monto | `monto_centavos` | Monto | importe en centavos | no | capturado | mutable | — | — | financiera |
| Estado | `estado` | Vigente / cumplida / incumplida | código de catálogo | sí | capturado | mutable | VIGENTE | — | — |

---

## 3. `jornadas_cobrador`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-22 | — |
| Cobrador | `cobrador_id` | Usuario | identificador | sí | capturado | inmutable | — | UK (cobrador, fecha) | — |
| Fecha operativa | `fecha_operativa` | Día | fecha | sí | capturado | inmutable | — | creación perezosa | — |
| Estado | `estado` | ABIERTA / EN_CORTE / CERRADA | código de catálogo | sí | capturado | mutable | ABIERTA | — | — |
| Saldo inicial autorizado | `saldo_inicial_centavos` | Snapshot | importe en centavos | sí | generado | inmutable | — | — | financiera |
| Noches permanencia | `noches_permanencia` | Racha efectivo > 0 | número entero | sí | derivado | proyectado | 0 | D-15, D-38, D-46 | — |

## 4. `excepciones_permanencia_efectivo`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-38, D-46, UC-27 | — |
| Cobrador | `cobrador_id` | Usuario | identificador | sí | capturado | inmutable | — | — | — |
| Jornada | `jornada_cobrador_id` | Relacionada | identificador | no | capturado | inmutable | — | — | — |
| Corte | `corte_cobrador_id` | Relacionado | identificador | no | capturado | inmutable | — | — | — |
| Importe autorizado | `importe_autorizado_centavos` | Tope | importe en centavos | sí | capturado | inmutable | — | no altera disponible | financiera |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Fecha límite | `fecha_limite` | Límite 4.ª noche | fecha | sí | capturado | inmutable | — | no 5.ª noche | — |
| Administrador | `administrador_id` | Autorizador | identificador | sí | capturado | inmutable | — | — | auditoría |
| Estado | `estado` | Vigente / usada / anulada | código de catálogo | sí | capturado | mutable | VIGENTE | — | — |
| Observaciones | `observaciones` | Texto | texto largo | no | capturado | inmutable | — | — | — |

## 5. `cortes_cobrador`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | RN-COR | — |
| Jornada | `jornada_cobrador_id` | FK | identificador | sí | capturado | inmutable | — | versionada | — |
| Versión | `version` | N.º reapertura | versión | sí | generado | inmutable | 1 | — | — |
| Esperado | `esperado_centavos` | Calculado | importe en centavos | sí | derivado | inmutable | — | — | financiera |
| Contado | `contado_centavos` | Físico | importe en centavos | sí | capturado | inmutable | — | — | financiera |
| Diferencia | `diferencia_centavos` | Contado − esperado | importe en centavos | sí | derivado | inmutable | — | — | financiera |
| Conservado | `conservado_centavos` | Queda | importe en centavos | sí | capturado | inmutable | — | — | financiera |
| Entregado | `entregado_centavos` | Entrega | importe en centavos | sí | capturado | inmutable | — | — | financiera |
| Estado | `estado` | Estado corte | código de catálogo | sí | capturado | mutable | — | — | — |
| Cerrado en | `cerrado_en` | Fecha | fecha y hora | no | capturado | mutable | — | — | auditoría |

---

## 6. `cajas_centrales`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-39 | — |
| Código | `codigo` | Código único | texto corto | sí | capturado | inmutable | — | UK | — |
| Nombre | `nombre` | Etiqueta | texto corto | sí | capturado | mutable | — | — | — |
| Estado | `estado` | ACTIVA / INACTIVA | código de catálogo | sí | capturado | mutable | — | máx. 1 ACTIVA en V1 | — |

## 7. `jornadas_caja_central`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-40 | — |
| Caja | `caja_central_id` | FK | identificador | sí | capturado | inmutable | — | UK (caja, fecha) | — |
| Fecha operativa | `fecha_operativa` | Día | fecha | sí | capturado | inmutable | — | perezosa ABIERTA | — |
| Estado | `estado` | ABIERTA / EN_CORTE / CERRADA | código de catálogo | sí | capturado | mutable | ABIERTA | no PENDIENTE | — |
| Saldo inicial | `saldo_inicial_centavos` | Snapshot corte anterior | importe en centavos | sí | generado | inmutable | — | — | financiera |

## 8. `cortes_caja_central`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | UC-25 | — |
| Jornada | `jornada_caja_central_id` | FK | identificador | sí | capturado | inmutable | — | versionada | — |
| Versión | `version` | N.º | versión | sí | generado | inmutable | 1 | — | — |
| Esperado | `esperado_centavos` | Calculado | importe en centavos | sí | derivado | inmutable | — | — | financiera |
| Contado | `contado_centavos` | Físico | importe en centavos | sí | capturado | inmutable | — | — | financiera |
| Diferencia | `diferencia_centavos` | Diferencia | importe en centavos | sí | derivado | inmutable | — | — | financiera |
| Estado | `estado` | Estado | código de catálogo | sí | capturado | mutable | — | — | — |
| Cerrado en | `cerrado_en` | Fecha | fecha y hora | no | capturado | mutable | — | — | auditoría |

---

## 9. `motivos_entrega_fondo`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-50 | — |
| Código | `codigo` | FONDO_INICIAL / … / OTRO | código de catálogo | sí | capturado | inmutable | — | UK | — |
| Nombre | `nombre` | Etiqueta | texto corto | sí | capturado | mutable | — | — | — |
| Activo | `activo` | Disponible | booleano | sí | capturado | mutable | true | — | — |

## 10. `entregas_efectivo`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | UC-09, UC-24 | — |
| Tipo flujo | `tipo_flujo` | A_COBRADOR / A_CAJA_CENTRAL | código de catálogo | sí | capturado | inmutable | — | — | — |
| Motivo fondo | `motivo_entrega_fondo_id` | Si a cobrador | identificador | no | capturado | inmutable | — | D-50 | — |
| Declarado | `importe_declarado_centavos` | Declarado | importe en centavos | sí | capturado | inmutable | — | > 0 | financiera |
| Recibido | `importe_recibido_centavos` | Confirmado | importe en centavos | no | capturado | mutable | — | solo se mueve recibido D-47 | financiera |
| Folio | `folio` | Folio | texto corto | sí | generado | inmutable | — | — | — |
| Estado | `estado` | Máquina entrega | código de catálogo | sí | capturado | mutable | — | CONFIRMADA_CON_DIFERENCIA | — |
| Operación | `operacion_financiera_id` | Al confirmar | identificador | no | generado | inmutable | — | — | financiera |
| Jornada cobrador | `jornada_cobrador_id` | Relacionada | identificador | no | capturado | inmutable | — | — | — |
| Jornada caja | `jornada_caja_central_id` | Relacionada | identificador | no | capturado | inmutable | — | — | — |
| Cobrador | `cobrador_id` | Parte | identificador | no | capturado | inmutable | — | — | — |
| Caja | `caja_central_id` | Parte | identificador | no | capturado | inmutable | — | sale de CAJA_CENTRAL | — |

## 11. `gastos_ruta`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-66 | — |
| Operación | `operacion_financiera_id` | GASTO_RUTA | identificador | sí | generado | inmutable | — | UK; 1:1 con tipo | financiera |
| Jornada | `jornada_cobrador_id` | Día | identificador | sí | capturado | inmutable | — | 1:N jornada | — |
| Concepto | `concepto` | Descripción | texto corto | sí | capturado | inmutable | — | — | — |
| Importe | `importe_centavos` | Gasto | importe en centavos | sí | capturado | inmutable | — | > 0 | financiera |
| Autorizador | `autorizador_id` | Supervisor/admin | identificador | sí | capturado | inmutable | — | cobrador no autoautoriza | auditoría |
| Cobrador | `cobrador_id` | Quien gasta | identificador | sí | capturado | inmutable | — | — | — |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 12. `operaciones_tesoreria`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-54, D-64 | — |
| Operación | `operacion_financiera_id` | Folio | identificador | sí | generado | inmutable | — | UK; solo 6 tipos | financiera |
| Subtipo | `subtipo` | Código del tipo OF | código de catálogo | sí | capturado | inmutable | — | = tipo OF | — |
| Importe | `importe_centavos` | Importe | importe en centavos | sí | capturado | inmutable | — | > 0 | financiera |
| Documento | `documento_referencia` | Doc. soporte | texto corto | no | capturado | inmutable | — | — | — |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Contraparte | `contraparte_externa_id` | Opcional | identificador | no | capturado | inmutable | — | D-34 | — |
| Cuenta bancaria | `cuenta_bancaria_id` | Si aplica | identificador | no | capturado | inmutable | — | depósitos/retiros banco | — |
| Caja | `caja_central_id` | Caja | identificador | sí | capturado | inmutable | — | — | — |
| Jornada caja | `jornada_caja_central_id` | Día | identificador | no | capturado | inmutable | — | — | — |

## 13. `incidencias_caja`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | cash-differences | — |
| Tipo | `tipo` | FALTANTE / SOBRANTE | código de catálogo | sí | capturado | inmutable | — | — | — |
| Importe original | `importe_original_centavos` | Diferencia inicial | importe en centavos | sí | capturado | inmutable | — | > 0 | financiera |
| Importe resuelto | `importe_resuelto_centavos` | Suma resoluciones | importe en centavos | sí | derivado | proyectado | 0 | D-65 | financiera |
| Importe pendiente | `importe_pendiente_centavos` | Original − resuelto | importe en centavos | sí | derivado | proyectado | = original | D-65 | financiera |
| Estado | `estado` | ABIERTA / EN_REVISION / RESUELTA | código de catálogo | sí | capturado | mutable | ABIERTA | cierre explícito | — |
| Responsable | `responsable_id` | Usuario | identificador | no | capturado | mutable | — | — | — |
| Origen entrega | `entrega_efectivo_id` | Origen | identificador | no | capturado | inmutable | — | — | — |
| Origen corte cobrador | `corte_cobrador_id` | Origen | identificador | no | capturado | inmutable | — | — | — |
| Origen corte caja | `corte_caja_central_id` | Origen | identificador | no | capturado | inmutable | — | — | — |
| Cierre confirmado por | `cierre_confirmado_por_id` | Usuario | identificador | no | capturado | mutable | — | D-65 | auditoría |
| Cierre confirmado en | `cierre_confirmado_en` | Fecha | fecha y hora | no | capturado | mutable | — | pendiente = 0 | auditoría |
| Reapertura motivo | `reapertura_motivo` | Si reabre | texto largo | no | capturado | mutable | — | solo admin | auditoría |

## 14. `resoluciones_incidencia_caja`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-62, D-65 | — |
| Incidencia | `incidencia_caja_id` | FK | identificador | sí | capturado | inmutable | — | 1:N | — |
| Operación | `operacion_financiera_id` | Resolución | identificador | sí | generado | inmutable | — | UK 1:0..1 OF | financiera |
| Tipo | `tipo` | REPOSICION / ENTREGA / AJUSTE_… | código de catálogo | sí | capturado | inmutable | — | — | — |
| Importe aplicado | `importe_aplicado_centavos` | Parcial o total | importe en centavos | sí | capturado | inmutable | — | ≤ pendiente; > 0 | financiera |
| Usuario | `usuario_id` | Quien registra | identificador | sí | capturado | inmutable | — | — | auditoría |
| Observaciones | `observaciones` | Texto | texto largo | no | capturado | inmutable | — | — | — |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

---

## 15. `restricciones_cliente`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | RN-RES | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Estado | `estado` | ACTIVA / EN_REVISION / RETIRADA / PERMANENTE | código de catálogo | sí | capturado | mutable | ACTIVA | no auto-retira | — |
| Fecha revisión | `fecha_revision` | Revisión | fecha | no | capturado | mutable | — | no retira sola | — |
| Créditos relacionados | `creditos_relacionados` | Snapshot IDs | texto largo | no | capturado | inmutable | — | — | — |

## 16. `castigos_credito`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | UC-26, D-24 | — |
| Crédito | `credito_id` | FK | identificador | sí | capturado | inmutable | — | 1:1 UK | — |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Saldo snapshot | `saldo_snapshot_centavos` | Saldo al castigar | importe en centavos | sí | generado | inmutable | — | — | financiera |
| Usuario | `usuario_id` | Quién castiga | identificador | sí | capturado | inmutable | — | — | auditoría |
| Castigado en | `castigado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 17. `recuperaciones_credito_castigado`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-24 | — |
| Crédito | `credito_id` | Castigado | identificador | sí | capturado | inmutable | — | — | — |
| Operación | `operacion_financiera_id` | RECUPERACION_… | identificador | sí | generado | inmutable | — | 1:1 | financiera |
| Importe | `importe_centavos` | Recuperación | importe en centavos | sí | capturado | inmutable | — | > 0; reduce SALDO_CREDITO | financiera |
| Medio | `medio` | EFECTIVO / TRANSFERENCIA | código de catálogo | sí | capturado | inmutable | — | — | — |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | no reactiva ni renovación | auditoría |

---

## Referencias

- `docs/04-database/diccionario-libro-auditoria-procesos.md`
- `docs/01-requirements/cash-differences.md`
