# CREDIMEX — Modelo de movimientos financieros

**Estado:** Aprobado (Fase 3A)  
**Patrón:** Operaciones de dominio específicas + libro operativo común.  
**Fuentes:** Decisiones v1.3 (D-04, D-11, D-17), Decisiones modelo v1.4
(D-21 a D-32), Decisiones modelo v1.5 (D-33 a D-50), UC-04, UC-05, UC-07,
UC-09, UC-24, UC-25, UC-26, UC-27, `cash-differences.md`,
`catalogo-operaciones-financieras.md`.

---

## 1. Principios

1. Ninguna operación financiera se elimina físicamente.
2. Las correcciones ordinarias de pagos se hacen mediante **reversos**.
3. Los saldos son **reconstruibles** desde `MovimientoCuenta`.
4. Los importes se expresan en **centavos enteros**.
5. Una transferencia bancaria **no** incrementa efectivo físico.
6. No se modifica un pago para cuadrar caja.
7. El libro es **operativo**, no una contabilidad fiscal completa.
8. Signo: **positivo aumenta** el saldo de la cuenta; **negativo disminuye**.

---

## 2. Piezas del libro

### OperacionFinanciera

Agrupa un hecho de negocio en un **folio** único.

Datos conceptuales: tipo, folio, clave de idempotencia, usuarios, fecha
operativa, estado lógico, motivo, medio (si aplica), `operacion_padre_id`
opcional, referencias a entidades de dominio.

El tipo es un **catálogo cerrado de 20 códigos** (D-33). Lista canónica en
`catalogo-operaciones-financieras.md`.

Familias:

- crédito y desembolso (`DESEMBOLSO_CREDITO`, `PRIMER_PAGO_RETENIDO`,
  `PAGO`, `REVERSO_PAGO`, `REESTRUCTURACION_CREDITO`,
  `RECUPERACION_CREDITO_CASTIGADO`, `AJUSTE_ADMINISTRATIVO_DESEMBOLSO`);
- caja del cobrador (`ENTREGA_EFECTIVO_A_COBRADOR`,
  `ENTREGA_EFECTIVO_A_CAJA_CENTRAL`, `GASTO_RUTA`, `REPOSICION_FALTANTE`,
  `AJUSTE_EFECTIVO_COBRADOR`);
- caja central y banco (`APORTACION_CAPITAL`, `INGRESO_EXTRAORDINARIO`,
  `DEPOSITO_BANCARIO_MANUAL`, `RETIRO_BANCARIO_A_CAJA_CENTRAL`,
  `RETIRO_CAJA_CENTRAL`, `GASTO_CAJA_CENTRAL`, `AJUSTE_CAJA_CENTRAL`);
- migración (`CARGA_INICIAL_SALDO`).

### CuentaOperativa

Tipos iniciales:

| Tipo | Dueño | Uso |
|---|---|---|
| `EFECTIVO_COBRADOR` | `Usuario` cobrador | Efectivo físico del cobrador |
| `CAJA_CENTRAL` | `CajaCentral` | Efectivo de tesorería central |
| `CUENTA_BANCARIA` | `CuentaReceptora` | Saldos registrados en cuentas |
| `SALDO_CREDITO` | `Credito` | Saldo por cobrar del crédito |

Proyección: `saldo_actual_centavos` + control de versión.

### MovimientoCuenta

Registro append-only:

- `operacion` (folio);
- `cuenta`;
- `importe_con_signo` (centavos);
- `concepto` (opcional; obligatorio cuando hay varios asientos sobre la
  misma cuenta en la misma operación);
- metadatos.

Transferencias internas: **al menos dos** movimientos con el mismo folio.

Entradas/salidas externas: movimiento sobre la cuenta de custodia real,
acompañado de contraparte conceptual, documento y motivo.

### ReservaEfectivo (D-26)

No es `MovimientoCuenta`.

- Se crea con desembolso `PENDIENTE_CONFIRMACION`.
- Reduce el **disponible** del cobrador.
- Se **libera** al `CANCELADO_ANTES_DE_ENTREGA`.
- Se **consume** al `CONFIRMADO` (entonces sí nacen movimientos definitivos).

Disponible conceptual:

```text
disponible = saldo_actual_EFECTIVO_COBRADOR - sum(reservas ACTIVAS)
```

---

## 3. Proyecciones

### Credito

- `saldo_actual_centavos`
- `dias_atraso_actual`
- `semaforo_actual`
- `fecha_calculo_atraso`
- `cuotas_vencidas_pendientes`

### CuentaOperativa

- `saldo_actual_centavos`
- `version`

Reglas:

- se actualizan en la **misma transacción** que el `MovimientoCuenta`;
- nunca cambian sin movimiento (salvo creación en cero);
- reconciliables contra la suma del libro;
- el atraso se recalcula tras pagos, reversos, reestructuras, cambios de
  calendario y proceso diario (D-28, D-37).

---

## 4. Idempotencia

Toda escritura financiera desde el cliente lleva clave única.

Si se reintenta la misma clave, la API devuelve el resultado de la
`OperacionFinanciera` existente sin crear otro movimiento.

---

## 5. Reversos

Aplican a pagos (y a efectos asociados de efectivo/saldo/cuotas).

1. Se crea nueva `OperacionFinanciera` de reverso.
2. Se generan `MovimientoCuenta` inversos.
3. El pago original queda `REVERTIDO`.
4. Se recalculan aplicaciones / atraso / proyecciones.
5. Motivo obligatorio.

**Prohibido** usar reverso de pago para cuadrar faltantes o sobrantes.

Desembolso `CONFIRMADO`: no tiene reverso ordinario. Usar
`AJUSTE_ADMINISTRATIVO_DESEMBOLSO` (D-30).

---

## 6. Reconciliación

Periódica o bajo demanda:

```text
suma(MovimientoCuenta de la cuenta) == CuentaOperativa.saldo_actual_centavos
```

Desvíos generan alerta operativa; no se “arreglan” editando movimientos.

---

## 7. Ciclo de SALDO_CREDITO (D-21)

1. Al crear el crédito: cuenta `SALDO_CREDITO` con saldo **0**.
2. Al confirmar desembolso:

```text
SALDO_CREDITO + total_a_pagar
total_a_pagar = monto + interés
```

3. Comisión **no** forma parte del saldo.
4. Pagos posteriores: `SALDO_CREDITO - importe`.

---

## 8. Ejemplos — modalidades de desembolso (movimientos brutos, D-35)

Supuesto: monto 500_000 centavos ($5,000); comisión 100_000 ($1,000);
interés según plan; `total_a_pagar = monto + interés`.

Al confirmar siempre:

```text
OperacionFinanciera: DESEMBOLSO_CREDITO
SALDO_CREDITO + total_a_pagar          # concepto ACTIVACION_SALDO
Comision LIQUIDADA
ReservaEfectivo → CONSUMIDA
Credito → ACTIVO
Desembolso guarda modalidad y efectivo_neto
```

La comisión **siempre** genera `MovimientoCuenta` con concepto
`COBRO_COMISION`, aunque el neto entregado la absorba.

### Modalidad 1 — Préstamo completo y comisión aparte

```text
EFECTIVO_COBRADOR - monto      # DESEMBOLSO_PRINCIPAL
EFECTIVO_COBRADOR + comision   # COBRO_COMISION
```

Neto de efectivo del cobrador: `- monto + comision`.

### Modalidad 2 — Comisión descontada del dinero entregado

Aunque el cliente recibe `monto - comision`, los asientos son brutos:

```text
EFECTIVO_COBRADOR - monto      # DESEMBOLSO_PRINCIPAL
EFECTIVO_COBRADOR + comision   # COBRO_COMISION
```

Efectivo neto: `- (monto - comision)`. Ejemplo: −5 000 + 1 000 = −4 000.

### Modalidad 3 — Comisión y primer pago descontados

Supuesto adicional: primer pago retenido 22_500 centavos ($225).

```text
OperacionFinanciera: DESEMBOLSO_CREDITO
EFECTIVO_COBRADOR - monto      # DESEMBOLSO_PRINCIPAL
EFECTIVO_COBRADOR + comision   # COBRO_COMISION
SALDO_CREDITO     + total_a_pagar

OperacionFinanciera: PRIMER_PAGO_RETENIDO
  operacion_padre_id → DESEMBOLSO_CREDITO
EFECTIVO_COBRADOR + primer_pago
SALDO_CREDITO     - primer_pago
+ Pago (medio RETENIDO_DESEMBOLSO)
+ AplicacionPagoCuota
+ Ticket «PRIMER PAGO RETENIDO» (D-45)
```

Efectivo neto entregado: `monto - comision - primer_pago`
(ejemplo: 5 000 − 1 000 − 225 = 3 775 → movimiento neto −3 775).

Antes de confirmar: `ReservaEfectivo` por el efectivo neto que se espera
salir del cobrador según la modalidad.

---

## 9. Ejemplos — pagos

### Pago en efectivo

```text
OperacionFinanciera: PAGO (medio EFECTIVO)
EFECTIVO_COBRADOR + importe
SALDO_CREDITO     - importe
+ Pago CONFIRMADO
+ AplicacionPagoCuota (FIFO)
+ Ticket
+ recálculo atraso
```

Requiere jornada `ABIERTA` (o `REABIERTA` autorizada).

### Transferencia bancaria

```text
OperacionFinanciera: PAGO (medio TRANSFERENCIA)
CUENTA_BANCARIA + importe
SALDO_CREDITO   - importe
+ TransferenciaBancaria + Pago
+ AplicacionPagoCuota
```

**Sin** movimiento en `EFECTIVO_COBRADOR`.

### Reverso de pago (efectivo)

```text
OperacionFinanciera: REVERSO_PAGO
  operacion_padre_id → PAGO original
EFECTIVO_COBRADOR - importe
SALDO_CREDITO     + importe
Pago → REVERTIDO
```

### Reverso de primer pago retenido (D-45)

```text
OperacionFinanciera: REVERSO_PAGO
  operacion_padre_id → PRIMER_PAGO_RETENIDO
EFECTIVO_COBRADOR - importe
SALDO_CREDITO     + importe
```

Requiere devolución física al cliente; solo supervisor/administrador.

---

## 10. Ejemplos — fondos y entregas

### Entrada externa a custodia (D-25, D-50)

`APORTACION_CAPITAL` / `INGRESO_EXTRAORDINARIO` (y reposiciones):

```text
CAJA_CENTRAL + importe
# o CUENTA_BANCARIA + importe
+ documento, motivo y contraparte opcional
```

No se carga `EFECTIVO_COBRADOR` desde un origen abstracto.

### Fondo ordinario al cobrador (UC-24, D-50)

Tras doble confirmación:

```text
OperacionFinanciera: ENTREGA_EFECTIVO_A_COBRADOR
CAJA_CENTRAL      - importe
EFECTIVO_COBRADOR + importe
+ motivo (FONDO_INICIAL | FONDO_ADICIONAL | PARA_DESEMBOLSO |
         OPERACION_GENERAL | OTRO)
```

Misma operación / folio; usuarios, fecha, importe y auditoría.

### Entrega cobrador → caja central (UC-09, D-47)

Sin diferencia:

```text
OperacionFinanciera: ENTREGA_EFECTIVO_A_CAJA_CENTRAL
EFECTIVO_COBRADOR - importe
CAJA_CENTRAL      + importe
EntregaEfectivo → CONFIRMADA
```

Con diferencia (declarado 10 000, recibido 9 500):

```text
EFECTIVO_COBRADOR -9500
CAJA_CENTRAL      +9500
EntregaEfectivo → CONFIRMADA_CON_DIFERENCIA
+ IncidenciaCaja por 500 (permanece en cuenta origen)
```

---

## 11. Recuperaciones post-castigo (D-24)

```text
OperacionFinanciera: RECUPERACION_CREDITO_CASTIGADO
SALDO_CREDITO     - importe
EFECTIVO_COBRADOR + importe   # o CUENTA_BANCARIA + importe
```

`CastigoCredito` conserva snapshot del saldo al castigar.

Efectos **no** permitidos:

- reactivar el crédito;
- contar en el máximo de cinco;
- retirar restricción automáticamente;
- habilitar renovación.

---

## 12. Faltantes y sobrantes

```text
diferencia = efectivo_contado - efectivo_esperado
```

1. Se crea `IncidenciaCaja` (`ABIERTA`). **No** genera `OperacionFinanciera`.
2. No se ajustan saldos automáticamente.
3. Recuperación de faltante:

```text
OperacionFinanciera: REPOSICION_FALTANTE
EFECTIVO_COBRADOR o CAJA_CENTRAL + importe_recuperado
# según quien reponga, documentado
```

4. Sobrante: resolución auditada vía `AJUSTE_EFECTIVO_COBRADOR` o
   `AJUSTE_CAJA_CENTRAL` (con incidencia). Solo el **administrador** aprueba
   y ejecuta el ajuste (D-52); el supervisor puede documentar y solicitar.
5. **Prohibido** revertir o editar pagos para cuadrar.

---

## 13. Ajuste administrativo de desembolso (D-30, D-41)

Tipo: `AJUSTE_ADMINISTRATIVO_DESEMBOLSO`.

Solo administrador. Requiere incidencia, motivo, descripción, importe,
operación afectada, administrador, fecha; evidencia según `tipo_sustento`
(`SIN_DOCUMENTO` / `CON_DOCUMENTO`).

No elimina ni revierte físicamente el desembolso `CONFIRMADO`.

Los movimientos compensatorios dependen del error detectado (efectivo,
comisión, saldo) y quedan ligados a la incidencia vía
`operacion_padre_id` / `incidencia_caja_id`.

---

## 14. Gastos, depósitos, retiros y carga inicial

### Gasto autorizado (ruta o central)

```text
EFECTIVO_COBRADOR - importe   # GASTO_RUTA
# o CAJA_CENTRAL - importe    # GASTO_CAJA_CENTRAL
+ Gasto + autorización
```

### Depósito bancario manual

```text
OperacionFinanciera: DEPOSITO_BANCARIO_MANUAL
CAJA_CENTRAL    - importe
CUENTA_BANCARIA + importe
```

Sin conciliación bancaria automática.

### Retiro bancario a caja central

```text
OperacionFinanciera: RETIRO_BANCARIO_A_CAJA_CENTRAL
CUENTA_BANCARIA - importe
CAJA_CENTRAL    + importe
```

### Retiro autorizado de caja central

```text
OperacionFinanciera: RETIRO_CAJA_CENTRAL
CAJA_CENTRAL - importe
+ documento y motivo
```

### Carga inicial de saldos (D-42, D-51)

```text
OperacionFinanciera: CARGA_INICIAL_SALDO
CAJA_CENTRAL o EFECTIVO_COBRADOR o CUENTA_BANCARIA + importe
# u otra CuentaOperativa autorizada en migración documentada
+ evidencia obligatoria, motivo, fecha/hora de corte,
  responsable del conteo, administrador, referencia_lote, idempotencia
```

Una operación por cuenta y saldo cargado; varias pueden compartir lote.
Solo durante la puesta en marcha; bloqueada después del primer cierre de
fecha operativa. No es aportación nueva. Correcciones posteriores: ajuste
administrativo. Proceso:
`docs/03-processes/puesta-en-marcha-carga-inicial.md`.

---

## 15. Reestructuración (D-44)

```text
nuevo_interes = saldo_pendiente × tasa_del_nuevo_plazo
nuevo_total   = saldo_pendiente + nuevo_interes

OperacionFinanciera: REESTRUCTURACION_CREDITO
SALDO_CREDITO + nuevo_interes    # concepto INTERES_REESTRUCTURA
+ VersionCondicionesCredito
+ Calendario VIGENTE (anterior REEMPLAZADO)
+ cuotas pendientes → CANCELADA_POR_REESTRUCTURA
+ recálculo atraso
```

No crea crédito nuevo ni entrega dinero adicional.

---

## 16. Orquestación transaccional mínima (pago)

En una sola transacción:

1. Validar idempotencia.
2. Validar jornada `ABIERTA`, asignación, crédito `ACTIVO`, corte no cerrado.
3. Bloquear o verificar versión de `Credito` y cuentas.
4. Crear `OperacionFinanciera` y `Pago`.
5. Crear `MovimientoCuenta`.
6. Actualizar proyecciones de cuentas y crédito.
7. Crear `AplicacionPagoCuota` y recalcular atraso.
8. Crear auditoría y ticket.
9. Confirmar.

Si falla cualquier paso, no queda estado parcial.
