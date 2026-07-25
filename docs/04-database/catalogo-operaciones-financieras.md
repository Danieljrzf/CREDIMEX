# CREDIMEX — Catálogo de operaciones financieras

**Estado:** Aprobado (Fase 3A)  
**Versión:** 1.5  
**Alcance:** Catálogo cerrado de códigos de `OperacionFinanciera`, medios y
conceptos de `MovimientoCuenta`. Sin tipos SQL.  
**Fuentes:** Decisiones modelo v1.4 (D-21 a D-32), Decisiones modelo v1.5
(D-33 a D-50), `modelo-movimientos-financieros.md`.

---

## 1. Principios del catálogo

1. El tipo de `OperacionFinanciera` es un **catálogo cerrado** de 20 códigos.
2. El **medio** de cobro es atributo, no código (`EFECTIVO`, `TRANSFERENCIA`,
   `RETENIDO_DESEMBOLSO`).
3. Varios movimientos de la misma operación sobre la misma cuenta se distinguen
   por `MovimientoCuenta.concepto`.
4. Las entradas externas afectan la cuenta de custodia real
   (`CAJA_CENTRAL` o `CUENTA_BANCARIA`), no un origen abstracto.
5. Registrar una `IncidenciaCaja` **no** genera `OperacionFinanciera`.
6. Toda operación financiera lleva folio, idempotencia y auditoría.

---

## 2. Catálogo de medios

| Código | Uso |
|---|---|
| `EFECTIVO` | Cobro o recuperación en efectivo físico |
| `TRANSFERENCIA` | Cobro o recuperación verificado en cuenta receptora |
| `RETENIDO_DESEMBOLSO` | Primer pago retenido en el desembolso (D-49) |

---

## 3. Conceptos de MovimientoCuenta

Usados para identificar componentes dentro de una misma operación:

| Concepto | Operación típica | Significado |
|---|---|---|
| `DESEMBOLSO_PRINCIPAL` | `DESEMBOLSO_CREDITO` | Salida bruta del monto prestado |
| `COBRO_COMISION` | `DESEMBOLSO_CREDITO` | Entrada bruta de la comisión liquidada |
| `PRIMER_PAGO_RETENIDO` | `PRIMER_PAGO_RETENIDO` | Efecto del pago retenido en efectivo y saldo |
| `ACTIVACION_SALDO` | `DESEMBOLSO_CREDITO` | Carga de `SALDO_CREDITO` con `total_a_pagar` |
| `INTERES_REESTRUCTURA` | `REESTRUCTURACION_CREDITO` | Carga del interés nuevo sobre el saldo pendiente |

Otros movimientos pueden omitir concepto cuando el tipo de operación basta para
identificar el efecto.

---

## 4. Catálogo cerrado (20 códigos)

### 4.1 Crédito y desembolso

#### `DESEMBOLSO_CREDITO`

- **Familia:** crédito / desembolso.
- **Cuentas:** `EFECTIVO_COBRADOR` (conceptos `DESEMBOLSO_PRINCIPAL` y
  `COBRO_COMISION`); `SALDO_CREDITO` (concepto `ACTIVACION_SALDO`).
- **Movimientos brutos (D-35):** siempre se registran por separado principal y
  comisión, aunque el efectivo neto entregado sea menor.
- **Rol mínimo:** cobrador (con autorización previa según límite).
- **Jornada cobrador:** sí. **Jornada caja central:** no.
- **Doble confirmación:** no (confirmación del desembolso).
- **Incidencia:** no. **Contraparte:** no. **Evidencia:** no ordinaria.
- **Reverso ordinario:** no. Corrección vía
  `AJUSTE_ADMINISTRATIVO_DESEMBOLSO`.
- **Padre:** no. Puede ser padre de `PRIMER_PAGO_RETENIDO`.

#### `PRIMER_PAGO_RETENIDO`

- **Familia:** crédito / desembolso.
- **Cuentas:** `EFECTIVO_COBRADOR +`; `SALDO_CREDITO −`.
- **Crea:** `Pago` con `medio = RETENIDO_DESEMBOLSO`, `AplicacionPagoCuota`,
  `Ticket` (D-45).
- **Padre:** `operacion_padre_id` → `DESEMBOLSO_CREDITO` (obligatorio).
- **Folio e idempotencia propios.**
- **Rol mínimo:** cobrador (en la confirmación del desembolso).
- **Reverso ordinario:** sí, independiente; solo supervisor/administrador;
  exige devolución física al cliente (D-45).

#### `PAGO`

- **Familia:** cobranza.
- **Medio:** `EFECTIVO` o `TRANSFERENCIA`.
- **Cuentas (EFECTIVO):** `EFECTIVO_COBRADOR +`; `SALDO_CREDITO −`.
- **Cuentas (TRANSFERENCIA):** `CUENTA_BANCARIA +`; `SALDO_CREDITO −`
  (sin tocar `EFECTIVO_COBRADOR`).
- **Crea:** `Pago`, `AplicacionPagoCuota`, `Ticket`; si transferencia,
  `TransferenciaBancaria`.
- **Jornada cobrador:** sí si `EFECTIVO`; no si `TRANSFERENCIA`.
- **Reverso ordinario:** sí (`REVERSO_PAGO`).

#### `REVERSO_PAGO`

- **Familia:** crédito / corrección.
- **Efecto:** invierte los movimientos de la operación de pago referida
  (`operacion_padre_id` o referencia al pago).
- **Rol mínimo:** supervisor / administrador.
- **Prohibido** usar para cuadrar faltantes o sobrantes.

#### `REESTRUCTURACION_CREDITO`

- **Familia:** crédito.
- **Cálculo (D-44):**
  - `nuevo_interes = saldo_pendiente × tasa_del_nuevo_plazo`
  - `nuevo_total = saldo_pendiente + nuevo_interes`
- **Movimiento:** `SALDO_CREDITO + nuevo_interes` (concepto
  `INTERES_REESTRUCTURA`).
- **Efectos de dominio:** nueva `VersionCondicionesCredito`; nuevo
  `Calendario` `VIGENTE`; calendario anterior `REEMPLAZADO`; cuotas pendientes
  `CANCELADA_POR_REESTRUCTURA`; recálculo de atraso.
- **No** crea crédito nuevo ni entrega dinero adicional.
- **Rol mínimo:** supervisor / administrador.
- **Reverso ordinario:** no.

#### `RECUPERACION_CREDITO_CASTIGADO`

- **Familia:** crédito / castigo.
- **Medio:** `EFECTIVO` o `TRANSFERENCIA`.
- **Cuentas:** `SALDO_CREDITO −`; `EFECTIVO_COBRADOR +` o
  `CUENTA_BANCARIA +` según medio.
- **No** reactiva el crédito, no cuenta en el máximo de cinco, no retira
  restricción, no habilita renovación (D-24).

#### `AJUSTE_ADMINISTRATIVO_DESEMBOLSO`

- **Familia:** crédito / ajuste.
- **Rol mínimo:** administrador.
- **Requiere:** incidencia, motivo, descripción, importe, operación
  afectada, administrador, fecha; evidencia según `tipo_sustento` (D-41).
- **No** elimina ni revierte físicamente el desembolso `CONFIRMADO`.

### 4.2 Caja del cobrador

#### `ENTREGA_EFECTIVO_A_COBRADOR`

- **Familia:** caja cobrador / fondo.
- **Cuentas:** `CAJA_CENTRAL −`; `EFECTIVO_COBRADOR +` (D-50).
- **Motivo de entrega:** `FONDO_INICIAL` | `FONDO_ADICIONAL` |
  `PARA_DESEMBOLSO` | `OPERACION_GENERAL` | `OTRO`.
- **Doble confirmación:** sí (`EntregaEfectivo`).
- **Jornada cobrador:** sí. **Jornada caja central:** sí.
- **`operacion_relacionada_id`:** opcional.

#### `ENTREGA_EFECTIVO_A_CAJA_CENTRAL`

- **Familia:** caja cobrador / entrega.
- **Cuentas:** `EFECTIVO_COBRADOR −`; `CAJA_CENTRAL +`.
- **Doble confirmación:** sí.
- **Diferencia (D-47):** solo se mueve el importe recibido; la diferencia
  permanece en la cuenta origen hasta resolver la incidencia.

#### `GASTO_RUTA`

- **Familia:** caja cobrador.
- **Cuentas:** `EFECTIVO_COBRADOR −`.
- **Rol:** cobrador no autoautoriza.

#### `REPOSICION_FALTANTE`

- **Familia:** incidencias.
- **Cuentas:** `EFECTIVO_COBRADOR +` o `CAJA_CENTRAL +` según quien reponga.
- **Requiere:** `IncidenciaCaja` de faltante.

#### `AJUSTE_EFECTIVO_COBRADOR`

- **Familia:** ajuste.
- **Cuentas:** `EFECTIVO_COBRADOR` ±.
- **Requiere:** incidencia, motivo, administrador, importe, cuenta
  afectada, evidencia u observación, auditoría completa (D-52).
- **Rol mínimo:** administrador (exclusivo).
- **Supervisor:** puede registrar incidencia, documentar, adjuntar evidencia,
  solicitar y dar seguimiento; **no** aprueba ni ejecuta movimiento.

### 4.3 Caja central y banco

#### `APORTACION_CAPITAL`

- **Familia:** tesorería.
- **Cuentas:** `CAJA_CENTRAL +` o `CUENTA_BANCARIA +`.
- **Contraparte:** tipicamente `APORTANTE`.
- **Evidencia / motivo:** obligatorios.

#### `INGRESO_EXTRAORDINARIO`

- **Familia:** tesorería.
- **Cuentas:** `CAJA_CENTRAL +` o `CUENTA_BANCARIA +`.
- **Evidencia / motivo:** obligatorios.

#### `DEPOSITO_BANCARIO_MANUAL`

- **Familia:** tesorería.
- **Cuentas:** `CAJA_CENTRAL −`; `CUENTA_BANCARIA +`.
- **Sin** conciliación bancaria automática.

#### `RETIRO_BANCARIO_A_CAJA_CENTRAL`

- **Familia:** tesorería.
- **Cuentas:** `CUENTA_BANCARIA −`; `CAJA_CENTRAL +`.
- **Simetría** de `DEPOSITO_BANCARIO_MANUAL`.

#### `RETIRO_CAJA_CENTRAL`

- **Familia:** tesorería.
- **Cuentas:** `CAJA_CENTRAL −`.
- **Documento y motivo:** obligatorios.

#### `GASTO_CAJA_CENTRAL`

- **Familia:** tesorería.
- **Cuentas:** `CAJA_CENTRAL −`.
- **Autorización:** requerida.

#### `AJUSTE_CAJA_CENTRAL`

- **Familia:** ajuste.
- **Cuentas:** `CAJA_CENTRAL` ±.
- **Requiere:** incidencia, motivo, administrador, importe, cuenta
  afectada, evidencia u observación, auditoría completa (D-52).
- **Rol mínimo:** administrador (exclusivo).
- **Supervisor:** puede registrar incidencia, documentar, adjuntar evidencia,
  solicitar y dar seguimiento; **no** aprueba ni ejecuta movimiento.

### 4.4 Migración / puesta en marcha

#### `CARGA_INICIAL_SALDO`

- **Familia:** migración / puesta en marcha.
- **Cuentas:** `CAJA_CENTRAL`, `EFECTIVO_COBRADOR`, `CUENTA_BANCARIA` u
  otra `CuentaOperativa` autorizada en una migración documentada (D-42,
  D-51).
- **Rol mínimo:** administrador.
- **Requiere:** cuenta, importe (centavos), fecha y hora de corte,
  responsable del conteo o validación, administrador, motivo, evidencia
  obligatoria, referencia de lote, idempotencia, auditoría.
- **Reglas:** una operación por cuenta y saldo cargado; varias pueden
  compartir lote; no es aportación nueva.
- **Ventana:** únicamente durante la puesta en marcha; **bloqueada** después
  del primer cierre de fecha operativa.
- **Corrección posterior:** no reutiliza este código; usa ajuste
  administrativo documentado.
- **Proceso:** `docs/03-processes/puesta-en-marcha-carga-inicial.md`.

---

## 5. Motivos de entrega al cobrador (D-50)

| Código | Significado |
|---|---|
| `FONDO_INICIAL` | Fondo de arranque de jornada o ruta |
| `FONDO_ADICIONAL` | Reabasto |
| `PARA_DESEMBOLSO` | Efectivo destinado a desembolsar |
| `OPERACION_GENERAL` | Operación ordinaria de caja |
| `OTRO` | Otro motivo documentado |

`OrigenComercialFondo` **no** forma parte del flujo ordinario. La entrega
siempre sale de `CAJA_CENTRAL`.

---

## 6. Códigos excluidos del catálogo

| Código propuesto | Tratamiento |
|---|---|
| `ACTIVACION_SALDO_CREDITO` | Eliminado; la activación es movimiento dentro de `DESEMBOLSO_CREDITO` |
| `COBRO_COMISION` (como operación) | Reclasificado a concepto de `MovimientoCuenta` |
| `DESEMBOLSO_PRINCIPAL` (como operación) | Reclasificado a concepto de `MovimientoCuenta` |
| `PAGO_EFECTIVO` / `PAGO_TRANSFERENCIA` | Fusionados en `PAGO` + atributo `medio` |
| `FONDO_ENTREGADO_A_COBRADOR` | Renombrado a `ENTREGA_EFECTIVO_A_COBRADOR` |
| `APORTACION_CAJA_CENTRAL` | Renombrado a `APORTACION_CAPITAL` |
| `TRANSFERENCIA_CAJA_A_BANCO` | Fusionado en `DEPOSITO_BANCARIO_MANUAL` |
| `TRANSFERENCIA_BANCO_A_CAJA` | Renombrado a `RETIRO_BANCARIO_A_CAJA_CENTRAL` |
| `REGISTRO_FALTANTE` / `REGISTRO_SOBRANTE` | Eliminados; crean `IncidenciaCaja`, no operación |
| `RESOLUCION_FALTANTE` | Fusionado en `REPOSICION_FALTANTE` |
| `RESOLUCION_SOBRANTE` | Absorbido por `AJUSTE_EFECTIVO_COBRADOR` / `AJUSTE_CAJA_CENTRAL` |
| `RECUPERACION_FALTANTE` | Renombrado a `REPOSICION_FALTANTE` |

---

## 7. Autorrelación `operacion_padre_id` (D-36)

Campo opcional de `OperacionFinanciera`. Usos:

- `PRIMER_PAGO_RETENIDO` → `DESEMBOLSO_CREDITO` (obligatorio);
- `REVERSO_PAGO` → operación de pago revertida;
- ajustes y movimientos compensatorios → operación afectada.

No se requiere `grupo_operacion_id`.

---

## 8. Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md`
- `docs/04-database/modelo-movimientos-financieros.md`
- `docs/04-database/catalogo-entidades.md`
- `docs/04-database/catalogo-estados.md`
