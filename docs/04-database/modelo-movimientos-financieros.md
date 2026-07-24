# CREDIMEX — Modelo de movimientos financieros

**Estado:** Aprobado (Fase 3A)  
**Patrón:** Operaciones de dominio específicas + libro operativo común.  
**Fuentes:** Decisiones v1.3 (D-04, D-11, D-17), Decisiones modelo v1.4
(D-21 a D-32), UC-04, UC-05, UC-07, UC-09, UC-24, UC-25, UC-26,
`cash-differences.md`.

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
operativa, estado lógico, motivo, referencias a entidades de dominio.

Tipos relevantes (lista conceptual, no exhaustiva de códigos SQL):

- pago de crédito;
- reverso de pago;
- desembolso confirmado;
- transferencia bancaria;
- fondo a cobrador / entrega de efectivo;
- gasto;
- aportación / ingreso extraordinario (entrada a custodia);
- depósito bancario manual;
- retiro;
- recuperación de crédito castigado;
- `AJUSTE_ADMINISTRATIVO_DESEMBOLSO` (D-30).

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
- metadatos.

Transferencias internas: **al menos dos** movimientos con el mismo folio.

Entradas/salidas externas: movimiento sobre la cuenta de custodia real,
acompañado de origen comercial, documento y motivo.

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
  calendario y proceso diario (D-28).

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

## 8. Ejemplos — modalidades de desembolso

Supuesto: monto 500_000 centavos ($5,000); comisión 100_000 ($1,000);
interés según plan; `total_a_pagar = monto + interés`.

Al confirmar siempre:

```text
SALDO_CREDITO + total_a_pagar
Comision LIQUIDADA
ReservaEfectivo → CONSUMIDA
Credito → ACTIVO
```

### Modalidad 1 — Préstamo completo y comisión aparte

La comisión se registra y liquida **en la misma operación**, no queda pendiente.

Efectivo del cobrador (ilustrativo):

```text
EFECTIVO_COBRADOR - monto          # entrega el préstamo completo
EFECTIVO_COBRADOR + comision       # recibe comisión en efectivo en el mismo acto
```

Neto de efectivo del cobrador: `- monto + comision`.

### Modalidad 2 — Comisión descontada del dinero entregado

```text
EFECTIVO_COBRADOR - (monto - comision)   # neto entregado al cliente
```

La comisión se liquida sin entrada separada de efectivo (ya descontada).

### Modalidad 3 — Comisión y primer pago descontados

```text
neto_cliente = monto - comision - primer_pago
EFECTIVO_COBRADOR - neto_cliente
SALDO_CREDITO - primer_pago              # además del + total_a_pagar
```

El primer pago retenido genera también `Pago` + `AplicacionPagoCuota` + ticket
si aplica, dentro de la misma `OperacionFinanciera` de desembolso o operación
hija ligada al mismo folio lógico.

Antes de confirmar: `ReservaEfectivo` por el efectivo que se espera salir del
cobrador según la modalidad.

---

## 9. Ejemplos — pagos

### Pago en efectivo

```text
OperacionFinanciera: PAGO
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
OperacionFinanciera: TRANSFERENCIA
CUENTA_BANCARIA + importe
SALDO_CREDITO   - importe
+ TransferenciaBancaria + Pago
+ AplicacionPagoCuota
```

**Sin** movimiento en `EFECTIVO_COBRADOR`.

### Reverso de pago (efectivo)

```text
OperacionFinanciera: REVERSO_PAGO
EFECTIVO_COBRADOR - importe
SALDO_CREDITO     + importe
Pago → REVERTIDO
```

---

## 10. Ejemplos — fondos y entregas

### Entrada externa a custodia (D-25)

Origen comercial `APORTACION_REGISTRADA` / `RECUPERACION` /
`INGRESO_EXTRAORDINARIO_AUTORIZADO`:

```text
CAJA_CENTRAL + importe
# o CUENTA_BANCARIA + importe
+ documento, motivo y origen comercial
```

No se carga `EFECTIVO_COBRADOR` desde el origen abstracto.

### Fondo ordinario al cobrador (UC-24, origen CAJA_CENTRAL)

Tras doble confirmación:

```text
OperacionFinanciera: FONDO_A_COBRADOR
CAJA_CENTRAL      - importe
EFECTIVO_COBRADOR + importe
```

Misma operación / folio; usuarios, fecha, importe y auditoría.

### Entrega cobrador → caja central (UC-09)

```text
EFECTIVO_COBRADOR - importe
CAJA_CENTRAL      + importe
```

Si hay diferencia: `IncidenciaCaja`; sin movimientos definitivos de cuadre
automático.

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

1. Se crea `IncidenciaCaja` (`ABIERTA`).
2. No se ajustan saldos automáticamente.
3. Recuperación de faltante:

```text
EFECTIVO_COBRADOR o CAJA_CENTRAL + importe_recuperado
# según quien reponga, documentado
```

4. Sobrante: resolución auditada que asigna el excedente a su origen correcto
   o lo registra según decisión administrativa.
5. **Prohibido** revertir o editar pagos para cuadrar.

---

## 13. Ajuste administrativo de desembolso (D-30)

Tipo: `AJUSTE_ADMINISTRATIVO_DESEMBOLSO`.

Solo administrador. Requiere incidencia, motivo, evidencia u observación,
movimientos compensatorios y auditoría completa.

No elimina ni revierte físicamente el desembolso `CONFIRMADO`.

Los movimientos compensatorios dependen del error detectado (efectivo,
comisión, saldo) y quedan ligados a la incidencia.

---

## 14. Gastos, depósitos y retiros (caja central)

### Gasto autorizado (ruta o central)

```text
EFECTIVO_COBRADOR - importe   # o CAJA_CENTRAL - importe
+ Gasto + autorización
```

### Depósito bancario manual

```text
CAJA_CENTRAL    - importe
CUENTA_BANCARIA + importe
```

Sin conciliación bancaria automática.

### Retiro autorizado

```text
CAJA_CENTRAL - importe
+ documento y motivo
```

---

## 15. Orquestación transaccional mínima (pago)

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
