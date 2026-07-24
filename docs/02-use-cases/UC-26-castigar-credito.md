# UC-26 — Castigar crédito

## Objetivo

Registrar el castigo de un crédito considerado incobrable, retirándolo de los
créditos operativos activos sin eliminar su historial financiero ni su saldo
histórico.

## Actores

- Administrador (autoriza y aplica el castigo).
- Supervisor, cuando la política le autoriza proponer el castigo.

## Precondiciones

- El crédito está desembolsado y conserva saldo.
- El crédito se encuentra en un estado operativo activo (`ACTIVO`, `ATRASADO`,
  `VENCIDO` o `REESTRUCTURADO_CON_SALDO`).
- El usuario tiene permiso para castigar créditos.
- Existe conexión con el servidor.
- Se captura un motivo obligatorio.

## Flujo principal

1. El usuario localiza el crédito incobrable.
2. Selecciona **Castigar crédito**.
3. El sistema muestra saldo pendiente, atraso, historial de pagos y cliente.
4. El usuario captura el motivo del castigo y las observaciones.
5. El sistema muestra los efectos: el crédito dejará de contar para el máximo de
   cinco y saldrá de la operación diaria.
6. El usuario confirma.
7. El sistema, en una sola transacción:
   - cambia el estado del crédito a `CASTIGADO`;
   - conserva el saldo histórico sin eliminarlo;
   - retira el crédito de la cobranza operativa y del conteo de créditos activos;
   - registra motivo, usuario, fecha y auditoría.

## Flujos alternativos

### A1. Restricción del cliente

Tras el castigo, el supervisor o administrador puede restringir al cliente
mediante UC-17. El castigo por sí mismo no restringe automáticamente al cliente.

### A2. Recuperación posterior

Si el cliente paga después de un castigo, la recuperación se registra como
movimiento auditado según la política de recuperaciones. El castigo no se elimina;
la recuperación queda relacionada con el crédito castigado.

## Excepciones

- **Crédito ya liquidado, cancelado o castigado:** no puede castigarse.
- **Crédito sin desembolso:** no aplica; primero debe existir un crédito operativo
  con saldo.
- **Sin motivo:** no se permite el castigo.
- **Sin conexión:** no se registra el castigo.

## Postcondiciones

- El crédito queda en estado `CASTIGADO`.
- El saldo histórico se conserva.
- El crédito ya no cuenta para el máximo de cinco créditos activos.
- El crédito desaparece de la cobranza operativa diaria.
- Se genera auditoría del castigo.

## Reglas

- Un crédito castigado puede conservar saldo histórico, pero queda fuera del
  límite de cinco porque ya no es un crédito operativo activo (D-09).
- El castigo no elimina físicamente el crédito ni sus movimientos.
- El castigo requiere motivo obligatorio y genera auditoría.
- El castigo no altera los créditos anteriores ni las condiciones de otros
  créditos del cliente.

## Criterios de aceptación

- Tras el castigo, el crédito deja de contar para el máximo de cinco créditos
  activos.
- El saldo histórico permanece consultable.
- El crédito no aparece en la ruta ni en la cobranza diaria.
- El castigo queda auditado con motivo, usuario y fecha.
- No es posible castigar un crédito liquidado, cancelado o ya castigado.
