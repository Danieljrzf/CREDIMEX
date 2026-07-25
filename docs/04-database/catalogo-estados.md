# CREDIMEX — Catálogo de estados

**Estado:** Aprobado (Fase 3A)  
**Fuentes:** Decisiones v1.3, Decisiones modelo v1.4, Decisiones modelo v1.5
(D-33 a D-50), `collector-workday.md`, casos de uso.

Para cada máquina: significado, estado inicial, transiciones, actor,
terminalidad y operaciones bloqueadas.

---

## 1. Usuario

| Estado | Significado |
|---|---|
| `ACTIVO` | Puede iniciar sesión según rol |
| `BLOQUEADO` | No puede iniciar sesión |

- **Inicial:** `ACTIVO`
- **Transiciones:** ACTIVO ↔ BLOQUEADO (administrador)
- **Terminal:** no
- **Bloquea:** `BLOQUEADO` → login y operaciones

---

## 2. Ruta

| Estado | Significado |
|---|---|
| `ACTIVA` | Puede recibir clientes y operar |
| `INACTIVA` | No recibe clientes nuevos |

- **Inicial:** `ACTIVA`
- **Transiciones:** ACTIVA ↔ INACTIVA (supervisor/administrador); desactivar
  con clientes requiere reasignación
- **Terminal:** no
- **Bloquea:** `INACTIVA` → altas nuevas en esa ruta

---

## 3. AsignacionRutaCobrador / AsignacionClienteRuta / AsignacionTemporalCobranza

| Estado | Significado |
|---|---|
| `VIGENTE` | Asignación activa |
| `FINALIZADA` | Terminó por cambio o vencimiento |
| `ANULADA` | Anulada con motivo |

- **Inicial:** `VIGENTE`
- **Actor:** supervisor / administrador
- **Terminal:** FINALIZADA, ANULADA
- **Bloquea:** solo `VIGENTE` habilita cobro/titularidad

---

## 4. Cliente

| Estado | Significado |
|---|---|
| `ACTIVO` | Operable |
| `INACTIVO` | Fuera de operación ordinaria |

- **Inicial:** `ACTIVO`
- **Terminal:** no
- **Nota:** la restricción es entidad aparte (`RestriccionCliente`)

---

## 5. RestriccionCliente

| Estado | Significado | Terminal |
|---|---|---|
| `ACTIVA` | Bloqueo vigente | No |
| `EN_REVISION` | En evaluación | No |
| `RETIRADA` | Retirada con decisión | Sí |
| `PERMANENTE` | Bloqueo permanente | Sí |

- **Inicial:** `ACTIVA`
- **Actor:** supervisor / administrador
- **Bloquea:** `ACTIVA` / `PERMANENTE` → nuevos créditos, renovaciones y
  reestructuras con dinero adicional
- **Nota:** la fecha de revisión no retira automáticamente

---

## 6. SolicitudCredito

| Estado | Significado | Terminal |
|---|---|---|
| `BORRADOR` | Captura incompleta o no enviada | No |
| `PENDIENTE_AUTORIZACION` | Esperando autorización | No |
| `DEVUELTA` | Devuelta para corrección | No |
| `APROBADA` | Aprobada; existe crédito `PENDIENTE_DESEMBOLSO` | **Sí** |
| `RECHAZADA` | Rechazada con motivo | **Sí** |
| `CANCELADA` | Cancelada | **Sí** |

- **Inicial:** `BORRADOR` (o `PENDIENTE_AUTORIZACION` si se envía completa)
- **Transiciones:**
  - BORRADOR → PENDIENTE_AUTORIZACION | CANCELADA
  - PENDIENTE_AUTORIZACION → APROBADA | RECHAZADA | DEVUELTA | CANCELADA
  - DEVUELTA → PENDIENTE_AUTORIZACION | CANCELADA
- **Actor:** cobrador / supervisor / administrador según límite (D-12)
- **No existe** `DESEMBOLSADA`
- **Bloquea:** cliente restringido; máximo de cinco créditos activos con saldo

Al pasar a `APROBADA` se crea un `Credito` en `PENDIENTE_DESEMBOLSO`.

---

## 7. Credito (estados principales)

| Estado | Significado | Terminal | Cuenta en máx. 5 |
|---|---|---|---|
| `PENDIENTE_DESEMBOLSO` | Creado al aprobar solicitud | No | No |
| `ACTIVO` | Desembolsado y operativo | No | **Sí si saldo > 0** |
| `LIQUIDADO` | Saldo en cero | Sí | No |
| `CANCELADO` | Cancelado antes de desembolso | Sí | No |
| `CASTIGADO` | Incobrable; puede conservar saldo histórico | Sí (operativo) | No |

- **Inicial:** `PENDIENTE_DESEMBOLSO`
- **Transiciones:**
  - PENDIENTE_DESEMBOLSO → ACTIVO (desembolso `CONFIRMADO`)
  - PENDIENTE_DESEMBOLSO → CANCELADO (D-23; solo antes de confirmar desembolso)
  - ACTIVO → LIQUIDADO (pago que deja saldo 0)
  - ACTIVO → CASTIGADO (UC-26)
- **No son estados principales:** ATRASADO, VENCIDO, “reestructurado”
- **Bloquea:**
  - `PENDIENTE_DESEMBOLSO` → no pagos de cobranza ordinaria
  - `CANCELADO` / `LIQUIDADO` / `CASTIGADO` → no cobranza ordinaria de ruta
  - `CASTIGADO` → no renovación; sí `RECUPERACION_CREDITO_CASTIGADO` (D-24)
  - `ACTIVO` → no cancelación ordinaria (D-23)

Clasificaciones derivadas (caché D-28): `dias_atraso_actual`, `semaforo_actual`,
`cuotas_vencidas_pendientes`.

---

## 8. Calendario

| Estado | Significado | Terminal |
|---|---|---|
| `VIGENTE` | Calendario actual del crédito | No |
| `REEMPLAZADO` | Sustituido por reestructura | Sí (histórico) |

- **Inicial:** `VIGENTE`
- **Transición:** VIGENTE → REEMPLAZADO al registrar reestructura; el nuevo
  calendario nace `VIGENTE`
- **Bloquea:** solo el `VIGENTE` genera cuotas exigibles y atraso

---

## 9. CuotaProgramada

| Estado | Significado | Terminal |
|---|---|---|
| `PROGRAMADA` | Aún no exigida en la fecha operativa | No |
| `PENDIENTE` | Exigible sin cobertura | No |
| `PARCIALMENTE_CUBIERTA` | Cobertura parcial | No |
| `CUBIERTA` | Cubierta al 100% | Sí |
| `CANCELADA_POR_REESTRUCTURA` | Calendario reemplazado | Sí |

- **Inicial:** `PROGRAMADA`
- **Transiciones:** PROGRAMADA → PENDIENTE (por fecha operativa);
  PROGRAMADA → PARCIALMENTE_CUBIERTA | CUBIERTA (pago retenido o anticipado,
  D-43); PENDIENTE ↔ PARCIALMENTE_CUBIERTA → CUBIERTA; cualquiera vigente
  del calendario → CANCELADA_POR_REESTRUCTURA
- **Actor:** sistema (aplicación de pagos / proceso)
- **Nota:** domingos y festivos **no generan filas**
- **Festivos (D-53):** al agregar/retirar festivo futuro solo se recalculan
  cuotas `PROGRAMADA` (conservan número, orden e importes; cambian fechas);
  no se regeneran automáticamente cuotas `PENDIENTE`,
  `PARCIALMENTE_CUBIERTA`, `CUBIERTA` ni históricas por cambios en fechas
  actuales o pasadas

---

## 10. Pago

| Estado | Significado | Terminal |
|---|---|---|
| `CONFIRMADO` | Aplicado | No |
| `REVERTIDO` | Anulado por reverso; registro conservado | Sí |

- **Inicial:** `CONFIRMADO`
- **Actor reverso:** supervisor / administrador
- **Bloquea:** pago revertido no afecta saldo ni cuotas activas

---

## 11. TransferenciaBancaria

| Estado | Significado |
|---|---|
| `REGISTRADA` | Verificada y aplicada |
| `ANULADA_POR_REVERSO` | Efecto anulado vía reverso del pago asociado |

- **Actor:** supervisor / administrador
- **Bloquea:** no incrementa `EFECTIVO_COBRADOR`

---

## 12. Desembolso

| Estado | Significado | Terminal |
|---|---|---|
| `PENDIENTE_CONFIRMACION` | Intento; reserva de efectivo activa | No |
| `CONFIRMADO` | Entrega registrada | Sí (operativo) |
| `CANCELADO_ANTES_DE_ENTREGA` | Abortado; reserva liberada | Sí |

- **Inicial:** `PENDIENTE_CONFIRMACION`
- **Transiciones:** PENDIENTE_CONFIRMACION → CONFIRMADO | CANCELADO_ANTES_DE_ENTREGA
- **Actor:** cobrador / supervisor / administrador
- **Restricción D-31:** máximo un `CONFIRMADO` por crédito; varios cancelados
  permitidos
- **Bloquea:**
  - sin `CONFIRMADO` el crédito no pasa a `ACTIVO`
  - no hay reverso ordinario de `CONFIRMADO` (D-30: ajuste administrativo)

---

## 13. Comision

| Estado | Significado |
|---|---|
| `LIQUIDADA` | Liquidada en el desembolso confirmado |

- **Inicial / único:** `LIQUIDADA` al confirmar desembolso
- **Terminal:** sí
- **Nota:** no existe comisión pendiente (D-11)

---

## 14. JornadaCobrador

Estados **persistidos** (D-22):

| Estado | Significado | Terminal |
|---|---|---|
| `ABIERTA` | Acepta operaciones financieras ordinarias | No |
| `EN_CORTE` | Corte en proceso | No |
| `CERRADA` | Conciliada | No |
| `REABIERTA` | Reapertura autorizada | No |

- **Inicial persistido:** `ABIERTA` (la fila nace con la primera operación)
- **`PENDIENTE`:** conceptual; **no se persiste**
- **Transiciones:**
  - ABIERTA → EN_CORTE (inicia corte)
  - EN_CORTE → ABIERTA (corte cancelado) | CERRADA (corte confirmado)
  - CERRADA → REABIERTA (reapertura con motivo)
  - REABIERTA → CERRADA (recorte)
- **Actor:** cobrador (abre por operación); supervisor/administrador (corte)
- **Bloquea:** solo `ABIERTA` (y `REABIERTA` para ajustes/reversos autorizados)
  acepta operaciones ordinarias; `EN_CORTE` / `CERRADA` no

---

## 15. EntregaEfectivo

| Estado | Significado | Terminal |
|---|---|---|
| `PENDIENTE_RECEPCION` | Declarada; sin confirmar | No |
| `CONFIRMADA` | Doble confirmación OK; importes iguales | Sí |
| `CONFIRMADA_CON_DIFERENCIA` | Confirmada con importe recibido ≠ declarado | Sí |
| `CANCELADA` | Abortada | Sí |

- **Inicial:** `PENDIENTE_RECEPCION`
- **Actor:** declarante + receptor (doble confirmación)
- **Reglas D-47:** solo se mueve el importe recibido; la diferencia permanece
  en la cuenta origen hasta resolver la incidencia
- **Bloquea:** sin `CONFIRMADA` ni `CONFIRMADA_CON_DIFERENCIA` no hay
  `MovimientoCuenta` definitivo de transferencia entre cuentas
- **Nota:** el estado `CON_DIFERENCIA` deja de usarse

---

## 16. IncidenciaCaja

| Estado | Significado | Terminal |
|---|---|---|
| `ABIERTA` | Diferencia registrada | No |
| `EN_REVISION` | En investigación | No |
| `RESUELTA` | Cerrada con decisión y movimientos si aplica | Sí |

- **Actor:** supervisor / administrador
- **Bloquea:** mientras abierta, no ajuste automático de saldos
- **Ajustes de efectivo (D-52):** el supervisor puede documentar y solicitar;
  solo el administrador aprueba y ejecuta `AJUSTE_EFECTIVO_COBRADOR` /
  `AJUSTE_CAJA_CENTRAL`

---

## 17. CorteCobrador / CorteCajaCentral

| Estado | Significado |
|---|---|
| `EN_PROCESO` | Conteo y diferencia en curso |
| `CERRADO` | Confirmado sin diferencia (o aceptado) |
| `CERRADO_CON_DIFERENCIA` | Cerrado con incidencia |
| `REABIERTO` | Versión invalidada por reapertura; se conserva |

- **Actor:** supervisor / administrador (caja central: administrador; supervisor
  según política)
- **Nota:** reapertura crea nueva versión; no elimina la anterior

---

## 18. CajaCentral

| Estado | Significado |
|---|---|
| `ACTIVA` | Operable |
| `INACTIVA` | Fuera de uso |

- **V1:** varias filas permitidas; solo una `ACTIVA` (D-29, D-39)
- **Inactivación:** solo con saldo cero y sin jornada abierta

---

## 19. JornadaCajaCentral

Estados **persistidos** (D-40):

| Estado | Significado | Terminal |
|---|---|---|
| `ABIERTA` | Acepta operaciones de tesorería ordinarias | No |
| `EN_CORTE` | Corte en proceso | No |
| `CERRADA` | Conciliada | No |
| `REABIERTA` | Reapertura autorizada | No |

- **Inicial persistido:** `ABIERTA` (la fila nace con la primera operación)
- **`PENDIENTE`:** conceptual; **no se persiste**
- **Unicidad:** `(caja_central, fecha_operativa)`
- **Transiciones:**
  - ABIERTA → EN_CORTE (inicia corte)
  - EN_CORTE → ABIERTA (corte cancelado) | CERRADA (corte confirmado)
  - CERRADA → REABIERTA (reapertura con motivo)
  - REABIERTA → CERRADA (recorte)
- **Actor:** administrador (y supervisor según política)
- **Creación:** perezosa, en la misma transacción que la operación que la
  origina

---

## 20. ReservaEfectivo

| Estado | Significado | Terminal |
|---|---|---|
| `ACTIVA` | Reduce disponible del cobrador | No |
| `CONSUMIDA` | Consumida al confirmar desembolso | Sí |
| `LIBERADA` | Liberada al cancelar intento | Sí |

- **Inicial:** `ACTIVA` al crear desembolso `PENDIENTE_CONFIRMACION`
- **Reglas:** D-26; no es `MovimientoCuenta`

---

## 21. Reestructuracion

| Estado | Significado |
|---|---|
| `REGISTRADA` | Evento append-only |

- No es estado del crédito
- **Actor:** supervisor / administrador
- **Operación:** `REESTRUCTURACION_CREDITO` (D-44)

---

## 22. Ticket

| Estado | Significado |
|---|---|
| `GENERADO` | Creado tras pago confirmado |
| `PENDIENTE_IMPRESION` | Falló o no se imprimió |
| `IMPRESO` | Impresión exitosa |

- Fallo de impresión **no** cancela el pago
- `PRIMER_PAGO_RETENIDO` también genera ticket (D-45)

---

## 23. OperacionFinanciera (estado lógico)

| Estado | Significado |
|---|---|
| `REGISTRADA` | Confirmada |
| `REVERTIDA` | Compensada por operación de reverso |
| `AJUSTADA` | Compensada por ajuste administrativo |

Tipos: catálogo cerrado de 20 códigos (D-33). Ver
`catalogo-operaciones-financieras.md`.

El reverso de `PRIMER_PAGO_RETENIDO` es independiente del desembolso padre
(D-45).

---

## 24. ContraparteExterna

| Estado | Significado |
|---|---|
| `ACTIVA` | Puede usarse en nuevas operaciones |
| `INACTIVA` | Fuera de uso; historial conservado |

- **Sin** borrado físico (D-34, D-48)

---

## 25. ExcepcionPermanenciaEfectivo

| Estado | Significado | Terminal |
|---|---|---|
| `AUTORIZADA` | Autorizada; aún no aplicada en corte | No |
| `APLICADA` | Usada en el corte de la cuarta noche | No |
| `CUMPLIDA` | Permanencia liquidada dentro de fecha límite | Sí |
| `VENCIDA` | Fecha límite sin resolución; genera incidencia | Sí |
| `REVOCADA` | Anulada antes de aplicar | Sí |

- **Inicial:** `AUTORIZADA`
- **Transiciones:**
  - AUTORIZADA → APLICADA | REVOCADA | VENCIDA
  - APLICADA → CUMPLIDA | VENCIDA
- **Restricciones:** `REVOCADA` solo desde `AUTORIZADA`; no quinta noche
  (D-38, D-46, UC-27)
