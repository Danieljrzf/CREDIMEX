# UC-25 — Operar caja central

## Objetivo

Registrar, conciliar y cortar los movimientos de la **caja central** de
CREDIMEX, que concentra el efectivo recibido de los cobradores, los fondos
entregados a cobradores y demás movimientos de tesorería operativa, sin
implementar integración bancaria automática.

## Actores

- Administrador (opera y corta la caja central).
- Supervisor, cuando la política le autoriza recibir o entregar efectivo.

## Precondiciones

- El usuario tiene permiso sobre la caja central.
- Existe conexión con el servidor.
- Existe una `CajaCentral` `ACTIVA` (D-39).

## Conceptos de la caja central

La caja central contempla los siguientes conceptos:

- **Saldo inicial** de la fecha operativa (snapshot del corte anterior).
- **Entregas recibidas de cobradores** (confirmadas mediante UC-09 /
  `ENTREGA_EFECTIVO_A_CAJA_CENTRAL`).
- **Fondos entregados a cobradores** (`ENTREGA_EFECTIVO_A_COBRADOR`, UC-24).
- **Aportaciones** de capital (`APORTACION_CAPITAL`).
- **Ingresos extraordinarios** (`INGRESO_EXTRAORDINARIO`).
- **Recuperaciones / reposiciones** provenientes de incidencias
  (`REPOSICION_FALTANTE`).
- **Gastos centrales** autorizados (`GASTO_CAJA_CENTRAL`).
- **Retiros autorizados** (`RETIRO_CAJA_CENTRAL`).
- **Depósitos bancarios registrados manualmente**
  (`DEPOSITO_BANCARIO_MANUAL`).
- **Retiros bancarios a caja central** (`RETIRO_BANCARIO_A_CAJA_CENTRAL`).
- **Ajustes documentados** (`AJUSTE_CAJA_CENTRAL`).
- **Carga inicial** durante puesta en marcha (`CARGA_INICIAL_SALDO`).
- **Efectivo esperado**, **efectivo contado** y **diferencias**.

## JornadaCajaCentral (D-40)

- Se crea de forma **perezosa** con la primera operación financiera del día.
- Nace en `ABIERTA`. No se persiste `PENDIENTE`.
- Unicidad: `(caja_central, fecha_operativa)`.
- La creación forma parte de la misma transacción que la operación que la
  origina.
- Estados: `ABIERTA`, `EN_CORTE`, `CERRADA`, `REABIERTA`.

## Fórmula del efectivo esperado

```text
efectivo esperado de caja central =
    saldo inicial
  + entregas recibidas de cobradores
  + aportaciones
  + ingresos extraordinarios
  + recuperaciones / reposiciones
  + retiros bancarios a caja central
  + carga inicial (solo puesta en marcha)
  - fondos entregados a cobradores
  - gastos centrales
  - retiros autorizados
  - depósitos bancarios registrados manualmente
  ± ajustes documentados
```

## Flujo principal

1. La primera operación del día crea la jornada en `ABIERTA` (o el
   administrador inicia un movimiento que la crea).
2. El sistema muestra el saldo inicial.
3. Durante la jornada se registran los movimientos listados en conceptos.
4. El sistema mantiene actualizado el efectivo esperado.
5. Al cierre, el administrador captura el efectivo contado.
6. El sistema calcula la diferencia.

```text
diferencia = efectivo contado - efectivo esperado
```

7. Si no hay diferencia, el administrador confirma el **corte diario de caja
   central**.
8. El sistema cierra el corte, define el saldo inicial del día siguiente,
   bloquea modificaciones ordinarias y registra auditoría.

## Flujos alternativos

### A1. Depósito bancario manual

El administrador registra un `DEPOSITO_BANCARIO_MANUAL` capturando importe,
fecha, referencia y cuenta. Reduce efectivo de caja central e incrementa
`CUENTA_BANCARIA`. Sin conciliación bancaria automática.

### A2. Retiro bancario a caja central

El administrador registra un `RETIRO_BANCARIO_A_CAJA_CENTRAL`: sale de
`CUENTA_BANCARIA` y entra a `CAJA_CENTRAL`.

### A3. Ajuste documentado

Cuando existe una causa justificada, el administrador registra un
`AJUSTE_CAJA_CENTRAL` con motivo e evidencia según `tipo_sustento` (D-41).
Nunca se aplica modificando pagos.

## Excepciones

- **Diferencia distinta de cero:** se genera una incidencia conforme a
  `cash-differences.md`; el corte puede quedar pendiente, cerrado con
  diferencia o en revisión.
- **Sin conexión:** no se confirman movimientos ni el corte.
- **Movimiento sin autorización o sin concepto:** no se registra.

## Postcondiciones

- Los movimientos de tesorería quedan registrados y auditados.
- El corte de caja central queda conciliado.
- El saldo inicial del día siguiente queda definido.
- Las diferencias quedan como incidencias rastreables.

## Reglas

- No se implementa integración bancaria automática; los depósitos y retiros
  bancarios se registran manualmente.
- Ningún movimiento de caja central se elimina físicamente; las correcciones
  se hacen mediante ajustes documentados.
- Las diferencias de caja central se tratan según `cash-differences.md`.
- No se modifican pagos para cuadrar la caja central.
- Todo movimiento y todo corte generan auditoría y folio único.
- Un fondo a cobrador (UC-24) genera salida en caja central y entrada al
  cobrador con el mismo folio, en una sola operación lógica.
- No se podrá aumentar el efectivo de un cobrador sin salir de
  `CAJA_CENTRAL` (D-50).

## Criterios de aceptación

- El efectivo esperado refleja exactamente la suma de los conceptos definidos.
- Las entregas de cobradores confirmadas y los fondos a cobradores concilian
  entre las cajas involucradas mediante el mismo folio.
- Un depósito bancario manual y un retiro bancario a caja central se reflejan
  en ambas cuentas sin conciliación automática.
- Una diferencia genera incidencia y no ajusta saldos automáticamente.
- El corte define el saldo inicial del día siguiente y conserva versiones ante
  reaperturas.
- La jornada nace de forma perezosa en `ABIERTA`.
