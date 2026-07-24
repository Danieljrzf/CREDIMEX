# UC-25 — Operar caja central

## Objetivo

Registrar, conciliar y cortar los movimientos de la **caja central** de CREDIMEX,
que concentra el efectivo recibido de los cobradores, los fondos entregados a
cobradores y demás movimientos de tesorería operativa, sin implementar integración
bancaria automática.

## Actores

- Administrador (opera y corta la caja central).
- Supervisor, cuando la política le autoriza recibir o entregar efectivo.

## Precondiciones

- El usuario tiene permiso sobre la caja central.
- Existe conexión con el servidor.
- La caja central tiene un saldo inicial definido para la fecha operativa.

## Conceptos de la caja central

La caja central contempla los siguientes conceptos:

- **Saldo inicial** de la fecha operativa.
- **Entregas recibidas de cobradores** (confirmadas mediante UC-09).
- **Fondos entregados a cobradores** (mediante UC-24 con origen
  `CAJA_CENTRAL`).
- **Aportaciones** de capital u origen externo, documentadas.
- **Recuperaciones** provenientes de incidencias de caja.
- **Gastos centrales** autorizados.
- **Retiros autorizados**.
- **Depósitos bancarios registrados manualmente** (sin integración automática).
- **Ajustes documentados** y autorizados.
- **Efectivo esperado**.
- **Efectivo contado**.
- **Diferencias**.

## Fórmula del efectivo esperado

```text
efectivo esperado de caja central =
    saldo inicial
  + entregas recibidas de cobradores
  + aportaciones
  + recuperaciones
  - fondos entregados a cobradores
  - gastos centrales
  - retiros autorizados
  - depósitos bancarios registrados manualmente
  ± ajustes documentados
```

## Flujo principal

1. El administrador abre la caja central de la fecha operativa.
2. El sistema muestra el saldo inicial.
3. Durante la jornada se registran los movimientos:
   - entregas recibidas de cobradores;
   - fondos entregados a cobradores con origen `CAJA_CENTRAL` (UC-24), que
     generan una salida en caja central y una entrada al cobrador con el mismo
     folio de transferencia, como una sola operación lógica;
   - aportaciones;
   - recuperaciones;
   - gastos centrales;
   - retiros autorizados;
   - depósitos bancarios registrados manualmente;
   - ajustes documentados.
4. El sistema mantiene actualizado el efectivo esperado.
5. Al cierre, el administrador captura el efectivo contado.
6. El sistema calcula la diferencia.

```text
diferencia = efectivo contado - efectivo esperado
```

7. Si no hay diferencia, el administrador confirma el **corte diario de caja
   central**.
8. El sistema cierra el corte, define el saldo inicial del día siguiente, bloquea
   modificaciones ordinarias y registra auditoría.

## Flujos alternativos

### A1. Depósito bancario manual

El administrador registra un depósito bancario capturando importe, fecha,
referencia y cuenta. El movimiento reduce el efectivo esperado de la caja central.
No se realiza ninguna conciliación bancaria automática.

### A2. Ajuste documentado

Cuando existe una causa justificada, el administrador registra un ajuste con
motivo obligatorio. El ajuste queda auditado y nunca se aplica modificando pagos.

## Excepciones

- **Diferencia distinta de cero:** se genera una incidencia conforme a
  `cash-differences.md`; el corte puede quedar pendiente, cerrado con diferencia o
  en revisión.
- **Sin conexión:** no se confirman movimientos ni el corte.
- **Movimiento sin autorización o sin concepto:** no se registra.

## Postcondiciones

- Los movimientos de tesorería quedan registrados y auditados.
- El corte de caja central queda conciliado.
- El saldo inicial del día siguiente queda definido.
- Las diferencias quedan como incidencias rastreables.

## Reglas

- No se implementa integración bancaria automática; los depósitos se registran
  manualmente.
- Ningún movimiento de caja central se elimina físicamente; las correcciones se
  hacen mediante reversos o ajustes documentados.
- Las diferencias de caja central se tratan según `cash-differences.md`.
- No se modifican pagos para cuadrar la caja central.
- Todo movimiento y todo corte generan auditoría y folio único.
- Un fondo a cobrador con origen `CAJA_CENTRAL` (UC-24) genera salida en caja
  central y entrada al cobrador con el mismo folio de transferencia, en una sola
  operación lógica, conservando usuarios, fecha, importe y auditoría.
- No se podrá aumentar el efectivo de un cobrador desde caja central sin ese
  origen registrado.

## Criterios de aceptación

- El efectivo esperado refleja exactamente la suma de los conceptos definidos.
- Las entregas de cobradores confirmadas y los fondos entregados a cobradores con
  origen `CAJA_CENTRAL` concilian entre las cajas involucradas mediante el mismo
  folio de transferencia.
- Un depósito bancario manual reduce el efectivo esperado sin conciliación
  automática.
- Una diferencia genera incidencia y no ajusta saldos automáticamente.
- El corte define el saldo inicial del día siguiente y conserva versiones ante
  reaperturas.
