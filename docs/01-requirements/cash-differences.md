# CREDIMEX — Diferencias de caja

**Estado:** Aprobado
**Relación:** Complementa `CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`
y `CREDIMEX_Decisiones_Resueltas_v1.3.md`

---

## Propósito

Definir el tratamiento de las diferencias de caja detectadas en entregas de
efectivo y en cortes, tanto del cobrador como de la caja central, sin alterar el
historial financiero.

Base de cálculo (documento maestro):

```text
diferencia = efectivo contado - efectivo esperado
```

---

## 1. Faltante

Un **faltante** ocurre cuando el efectivo contado es **menor** que el efectivo
esperado.

```text
faltante  ⇔  efectivo contado < efectivo esperado
```

El faltante nunca se corrige modificando pagos, comisiones ni desembolsos. Se
registra como una **incidencia** asociada al responsable y queda pendiente de
recuperación o resolución.

## 2. Sobrante

Un **sobrante** ocurre cuando el efectivo contado es **mayor** que el efectivo
esperado.

```text
sobrante  ⇔  efectivo contado > efectivo esperado
```

El sobrante también genera una **incidencia**. No se aplica automáticamente a
ningún crédito ni al saldo de caja: requiere investigación para identificar su
origen antes de cualquier resolución.

## 3. Incidencia

Toda diferencia distinta de cero genera una **incidencia** con:

- tipo (faltante o sobrante);
- importe de la diferencia;
- origen (entrega de efectivo o corte);
- cobrador, supervisor o caja relacionada;
- fecha operativa;
- explicación del cobrador;
- observación del receptor o supervisor;
- estado (abierta, en revisión, resuelta);
- responsable determinado;
- referencia de auditoría.

La incidencia es un registro append-only: no se elimina; se resuelve mediante
movimientos y decisiones auditadas.

**Registrar una incidencia no genera `OperacionFinanciera`.** Los códigos que
pueden resolverla son, según el caso:

- `REPOSICION_FALTANTE`;
- `AJUSTE_EFECTIVO_COBRADOR`;
- `AJUSTE_CAJA_CENTRAL`;
- entrega adicional confirmada;
- corrección auditada del conteo.

## 4. Responsable

Cada incidencia debe tener un **responsable determinado** por el supervisor o el
administrador. Mientras no exista una decisión, la incidencia permanece abierta y
**no se ajustan saldos automáticamente**.

El responsable puede ser el cobrador, el receptor del efectivo, la caja central o
quedar en revisión hasta esclarecer el origen.

## 5. Recuperación

La **recuperación** de un faltante se realiza mediante la operación
`REPOSICION_FALTANTE` (movimiento de efectivo explícito y auditado; por
ejemplo, reposición del responsable). La recuperación:

- se registra como movimiento independiente;
- referencia la incidencia que la origina;
- no modifica los pagos ni los movimientos financieros previos;
- cierra la incidencia cuando el importe recuperado cubre el faltante.

Para un sobrante, la resolución equivalente consiste en asignar el excedente a
su origen correcto o registrarlo mediante `AJUSTE_EFECTIVO_COBRADOR` /
`AJUSTE_CAJA_CENTRAL` según la decisión auditada del **administrador** (D-52).

El supervisor puede registrar la incidencia, documentar la diferencia,
adjuntar evidencia, solicitar el ajuste y darle seguimiento. El supervisor
**no** puede aprobar el ajuste, ejecutar `MovimientoCuenta` ni modificar
directamente saldos proyectados.

Todo ajuste de efectivo requiere: incidencia, motivo, administrador, importe,
cuenta afectada, evidencia u observación y auditoría completa.

## 5 bis. Diferencia en entrega de efectivo (D-47)

Cuando una `EntregaEfectivo` tiene declarado ≠ recibido, ambos usuarios
confirman el importe realmente recibido. **Solo el importe recibido** se mueve
entre cuentas. La diferencia permanece en la cuenta origen hasta resolver la
incidencia. La entrega termina en `CONFIRMADA_CON_DIFERENCIA`.

## 6. Efecto en el saldo inicial

El **saldo inicial del día siguiente** se determina a partir del efectivo
efectivamente conservado y autorizado en el corte, **no** a partir del efectivo
esperado teórico.

Una diferencia no resuelta:

- **no** se traslada silenciosamente al saldo inicial siguiente;
- permanece registrada como incidencia abierta hasta su resolución;
- solo afecta saldos cuando exista un movimiento de recuperación o un ajuste
  documentado y autorizado.

## 7. Prohibición de modificar pagos para cuadrar caja

Está **prohibido modificar, revertir o eliminar pagos** con el fin de cuadrar la
caja.

Los reversos de pago existen exclusivamente para corregir errores reales de
registro de un pago, con motivo obligatorio, y **nunca** como mecanismo de ajuste
de diferencias de efectivo. Las diferencias de caja se resuelven mediante
incidencias, recuperaciones y ajustes documentados, no tocando el historial de
cobranza.
