# UC-27 — Autorizar excepción de permanencia de efectivo (cuarta noche)

## Objetivo

Permitir que el **administrador** autorice, de forma excepcional y auditada,
que un cobrador conserve efectivo una **cuarta noche**, cuando el máximo
ordinario es de tres noches (D-14, D-15, D-38, D-46).

## Actores

- Administrador (autoriza, aplica, revoca o da por cumplida la excepción).
- Cobrador (conserva el efectivo autorizado en el corte).
- Supervisor (consulta; no autoriza la excepción).

## Precondiciones

- El cobrador tiene jornada con efectivo conservado o por conservar.
- Ya se alcanzó o se alcanzará el límite de tres noches consecutivas.
- No existe otra excepción `AUTORIZADA` o `APLICADA` vigente para el mismo
  cobrador en la misma racha de permanencia.
- No se ha autorizado previamente una cuarta noche para esa racha (no hay
  quinta noche).
- Existe conexión con el servidor.
- Se capturan importe, motivo, fecha límite y observaciones.

## Conceptos

- **Noches de permanencia:** racha de noches consecutivas con efectivo
  conservado mayor a cero al cierre.
- **Excepción:** registro `ExcepcionPermanenciaEfectivo` que desbloquea
  únicamente la cuarta noche, sin alterar el cálculo de disponible
  (`saldo − reservas activas`).
- **Fecha límite:** día operativo máximo en el que el efectivo autorizado
  debe entregarse o resolverse.

## Flujo principal

1. El administrador selecciona **Autorizar cuarta noche**.
2. Selecciona al cobrador y la jornada o corte relacionados.
3. Captura:
   - importe autorizado a conservar;
   - motivo;
   - fecha límite;
   - observaciones.
4. Confirma. El sistema crea `ExcepcionPermanenciaEfectivo` en estado
   `AUTORIZADA` y registra auditoría.
5. En el corte correspondiente, si el efectivo conservado no excede el
   importe autorizado y la fecha límite no ha vencido, el sistema permite la
   cuarta noche y pasa la excepción a `APLICADA`.
6. Cuando el cobrador entrega el efectivo o el corte siguiente liquida la
   permanencia dentro de la fecha límite, la excepción pasa a `CUMPLIDA`.

## Flujos alternativos

### A1. Revocación antes de aplicar

Si la excepción aún está `AUTORIZADA`, el administrador puede pasar a
`REVOCADA` con motivo. No se permite revocar después de `APLICADA`.

### A2. Vencimiento

Si llega la fecha límite sin entrega ni resolución, el sistema marca
`VENCIDA`, genera `IncidenciaCaja` y **bloquea una nueva permanencia** hasta
resolver la incidencia.

### A3. Intento de quinta noche

Si se solicita otra excepción sobre la misma racha, el sistema la rechaza.
Solo se permite una cuarta noche extraordinaria.

## Excepciones

- **Sin motivo, importe o fecha límite:** no se autoriza.
- **Cobrador sin racha que justifique cuarta noche:** no se autoriza.
- **Excepción ya aplicada o vencida en la racha:** no se autoriza otra.
- **Sin conexión:** no se registra la autorización.
- **Importe conservado mayor al autorizado en el corte:** no se permite la
  permanencia; se exige entrega o ajuste.

## Postcondiciones

- Queda registrada la excepción con estados y auditoría.
- El corte valida el importe autorizado.
- Si venció, queda incidencia abierta y bloqueo de nueva permanencia.
- No se altera el disponible del cobrador por la sola autorización.

## Reglas

- Máximo ordinario: tres noches (D-14).
- Cuarta noche solo con excepción administrativa auditada (D-15, D-38, D-46).
- No se permite quinta noche.
- No hay prórroga automática.
- `REVOCADA` solo desde `AUTORIZADA`.
- `VENCIDA` genera incidencia y bloquea nueva permanencia.
- La excepción no modifica pagos ni saldos por sí misma.

## Criterios de aceptación

- Sin excepción, el sistema bloquea conservar efectivo una cuarta noche.
- Con excepción `AUTORIZADA` vigente, el corte permite conservar hasta el
  importe autorizado.
- No es posible autorizar una quinta noche en la misma racha.
- Una excepción `VENCIDA` deja incidencia y bloquea nueva permanencia.
- Toda transición de estado queda auditada con usuario, fecha y motivo.

## Referencias

- `docs/01-requirements/collector-workday.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md` (D-38, D-46)
- `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md` (D-14, D-15)
- `docs/01-requirements/cash-differences.md`
