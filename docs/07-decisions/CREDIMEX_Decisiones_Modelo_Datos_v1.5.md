# CREDIMEX — Decisiones de Modelo de Datos

**Versión:** 1.5  
**Fecha:** 24 de julio de 2026  
**Estado:** Aprobado  
**Relación:** Complementa `CREDIMEX_Decisiones_Modelo_Datos_v1.4.md` (D-21 a
D-32) y `CREDIMEX_Decisiones_Resueltas_v1.3.md` (D-01 a D-20). Cierra las
decisiones abiertas A–H del modelo conceptual.

---

## Propósito

Documentar las decisiones D-33 a D-53 que cierran el catálogo de operaciones
financieras, las contrapartes, el primer pago retenido, la reconciliación de
atraso, la cuarta noche, la multi-caja, la jornada de caja central, la
evidencia de ajustes, la carga inicial, la reestructuración, las entregas con
diferencia, el motivo del fondo al cobrador, la puesta en marcha, la
autorización de ajustes de efectivo y la política de días festivos.

No sustituyen el documento maestro ni las decisiones v1.3/v1.4; las precisan.

---

## D-33 — Catálogo cerrado de OperacionFinanciera

El tipo de `OperacionFinanciera` es un **catálogo cerrado** de **20 códigos
activos**, documentados en
`docs/04-database/catalogo-operaciones-financieras.md`.

`MovimientoCuenta` incorpora un campo conceptual `concepto` para distinguir
varios asientos de la misma operación sobre la misma cuenta
(`DESEMBOLSO_PRINCIPAL`, `COBRO_COMISION`, `ACTIVACION_SALDO`,
`INTERES_REESTRUCTURA`, entre otros).

Registrar una `IncidenciaCaja` no genera `OperacionFinanciera`.

## D-34 — ContraparteExterna

Se crea la entidad `ContraparteExterna` (D-48) con tipos:

- `APORTANTE`
- `PROVEEDOR`
- `CLIENTE`
- `INSTITUCION_FINANCIERA_EXTERNA`
- `OTRO`

Datos: nombre, referencia, documento opcional, teléfono opcional,
observaciones, estado (`ACTIVA` / `INACTIVA`).

En `OperacionFinanciera` la contraparte es **opcional** y se conserva un
**snapshot** de nombre y referencia.

Si el tipo es `CLIENTE`, `cliente_id` es obligatorio.

Las cuentas bancarias propias de CREDIMEX se representan mediante
`CuentaReceptora` / `CuentaOperativa` `CUENTA_BANCARIA`, no como contraparte
externa.

## D-35 — Desembolso en movimientos brutos (precisa D-17)

La comisión genera un `MovimientoCuenta` identificable en las **tres**
modalidades de desembolso.

Aunque el efecto final sobre el efectivo sea neto, los componentes se
registran por separado:

```text
EFECTIVO_COBRADOR - monto     concepto DESEMBOLSO_PRINCIPAL
EFECTIVO_COBRADOR + comision  concepto COBRO_COMISION
```

Con primer pago retenido, además:

```text
# en la operación hija PRIMER_PAGO_RETENIDO
EFECTIVO_COBRADOR + primer_pago
SALDO_CREDITO     - primer_pago
```

La modalidad y el efectivo neto quedan guardados en `Desembolso`.

La comisión continúa fuera del saldo del crédito, liquidada al desembolsar y
sin generar deuda posterior.

Esta decisión **precisa D-17**: “movimiento identificable” se interpreta como
asiento con concepto propio, no necesariamente como operación financiera
separada (salvo el primer pago retenido, D-36).

## D-36 — Primer pago retenido y operacion_padre_id

`PRIMER_PAGO_RETENIDO` es una `OperacionFinanciera` **independiente** con:

- folio propio;
- idempotencia propia;
- entidad `Pago`;
- `AplicacionPagoCuota`;
- movimientos propios;
- posibilidad de reverso independiente (D-45).

Se relaciona con `DESEMBOLSO_CREDITO` mediante la autorrelación opcional
`operacion_padre_id`.

La misma autorrelación se reutiliza para reversos, ajustes y movimientos
compensatorios relacionados. No se requiere `grupo_operacion_id`.

## D-37 — Reconciliación diaria de atraso

El atraso se recalcula **inmediatamente** después de pagos, reversos,
reestructuras y cambios de calendario.

Además corre un proceso diario, inicialmente a las **00:10** en la zona horaria
de la operación. El horario es configurable (`ParametroSistema`) y el
administrador puede ejecutarlo manualmente.

Se registra en `EjecucionProcesoAtraso` (bitácora; no es
`OperacionFinanciera`). El proceso es reejecutable sin efectos secundarios
adicionales y marca cuotas exigibles además de actualizar las proyecciones
D-28.

## D-38 — Excepción de cuarta noche

La cuarta noche queda **bloqueada por defecto** (D-15).

Solo el administrador puede autorizar **una** noche adicional mediante
`ExcepcionPermanenciaEfectivo`, con cobrador, importe, motivo, fecha límite y
observaciones.

No se permite una quinta noche. No hay prórroga automática.

Estados: `AUTORIZADA`, `APLICADA`, `CUMPLIDA`, `VENCIDA`, `REVOCADA`
(`REVOCADA` solo antes de `APLICADA`).

`VENCIDA` genera incidencia y bloquea una nueva permanencia.

Detalle operativo en UC-27.

## D-39 — Multi-caja

V1 permite varias filas de `CajaCentral`, pero como regla de negocio solo una
puede estar `ACTIVA`.

Unicidad de `codigo`. No se agrega `sucursal_id`.

Una caja solo puede pasar a `INACTIVA` con saldo cero y sin jornada abierta.
El cambio de caja activa se audita.

## D-40 — JornadaCajaCentral

Estados persistidos:

- `ABIERTA`
- `EN_CORTE`
- `CERRADA`
- `REABIERTA`

La jornada se crea de forma **perezosa** con la primera operación financiera
del día y nace directamente en `ABIERTA`. No se persiste `PENDIENTE`.

Unicidad: `(caja_central, fecha_operativa)`.

La creación automática forma parte de la misma operación lógica y transacción
que el movimiento que origina la jornada.

## D-41 — Evidencia de operaciones y ajustes

`AJUSTE_ADMINISTRATIVO_DESEMBOLSO` requiere incidencia, motivo, descripción,
importe, operación afectada, administrador y fecha.

Se modela `EvidenciaOperacion` (append-only, ligada a `OperacionFinanciera`).

Campo `tipo_sustento`:

- `SIN_DOCUMENTO` — exige texto explicativo obligatorio; archivo opcional;
- `CON_DOCUMENTO` — exige al menos un archivo (comprobante, recibo,
  transferencia, acta, fotografía u otra evidencia documental).

El mismo criterio de evidencia aplica a `AJUSTE_EFECTIVO_COBRADOR`,
`AJUSTE_CAJA_CENTRAL`, `APORTACION_CAPITAL`, `INGRESO_EXTRAORDINARIO`,
`RETIRO_CAJA_CENTRAL`, `DEPOSITO_BANCARIO_MANUAL` y `CARGA_INICIAL_SALDO`.

## D-42 — CARGA_INICIAL_SALDO

Código aprobado para registrar el efectivo real existente al iniciar CREDIMEX,
sin confundirlo con una aportación nueva.

Puede afectar `CAJA_CENTRAL`, `EFECTIVO_COBRADOR` u otra `CuentaOperativa`
autorizada durante la migración inicial.

Reglas:

- solo administrador;
- evidencia obligatoria;
- motivo obligatorio;
- fecha de corte;
- importe;
- cuenta afectada;
- responsable del conteo;
- auditoría completa;
- idempotencia;
- permitida únicamente durante la puesta en marcha;
- bloqueada después de la primera fecha operativa cerrada.

Una corrección posterior no reutiliza este código; requiere un ajuste
administrativo documentado.

Detalle operativo de puesta en marcha: D-51 y
`docs/03-processes/puesta-en-marcha-carga-inicial.md`.

## D-43 — Transiciones de CuotaProgramada por pago retenido o anticipado

Se admiten las transiciones:

- `PROGRAMADA → PARCIALMENTE_CUBIERTA`
- `PROGRAMADA → CUBIERTA`

Aplican al `PRIMER_PAGO_RETENIDO` y a cualquier pago anticipado que cubra una
cuota aún no exigida por fecha operativa.

## D-44 — Reestructuración

`REESTRUCTURACION_CREDITO` forma parte del catálogo activo.

Cálculo sobre el saldo pendiente:

```text
nuevo_interes = saldo_pendiente × tasa_del_nuevo_plazo
nuevo_total   = saldo_pendiente + nuevo_interes
```

La cuenta `SALDO_CREDITO` ya contiene el saldo pendiente; la operación solo
registra:

```text
SALDO_CREDITO + nuevo_interes
```

La reestructuración:

- no crea crédito nuevo;
- no entrega dinero adicional;
- crea `VersionCondicionesCredito`;
- crea un nuevo `Calendario` `VIGENTE`;
- marca el calendario anterior `REEMPLAZADO`;
- marca sus cuotas pendientes `CANCELADA_POR_REESTRUCTURA`;
- conserva pagos y calendarios históricos;
- recalcula atraso y semáforo.

Precisa D-10 y la carga de `SALDO_CREDITO` descrita en D-21 (la carga inicial
sigue siendo solo al desembolso; el interés de reestructura es un asiento
adicional explícito).

## D-45 — Ticket del primer pago retenido

`PRIMER_PAGO_RETENIDO` genera `Pago` y `Ticket`.

El ticket muestra: leyenda «PRIMER PAGO RETENIDO», medio
`RETENIDO_DESEMBOLSO`, importe, saldo anterior, saldo nuevo, folio, crédito y
desembolso relacionado.

Se imprime después de confirmar el desembolso y el pago.

El reverso independiente invierte:

```text
EFECTIVO_COBRADOR - importe
SALDO_CREDITO     + importe
```

El reverso requiere devolución física al cliente y solo puede realizarlo
supervisor o administrador.

## D-46 — Detalle de la excepción de cuarta noche

Complementa D-38.

Solo se permite una cuarta noche extraordinaria. No se permite una quinta
noche.

Estados de `ExcepcionPermanenciaEfectivo`:

- `AUTORIZADA`
- `APLICADA`
- `CUMPLIDA`
- `VENCIDA`
- `REVOCADA` (solo antes de `APLICADA`)

`VENCIDA` genera incidencia y bloquea una nueva permanencia.

Caso de uso obligatorio: UC-27.

## D-47 — Entrega con diferencia

Cuando existe diferencia, ambos usuarios confirman el importe realmente
recibido. Solo el importe recibido se mueve entre cuentas.

Ejemplo: declarado 10 000, recibido 9 500 →

```text
EFECTIVO_COBRADOR -9500
CAJA_CENTRAL      +9500
```

La diferencia de 500 permanece en la cuenta origen hasta resolver la
incidencia.

La entrega termina en `CONFIRMADA_CON_DIFERENCIA`.

La incidencia puede resolverse mediante:

- entrega adicional;
- `REPOSICION_FALTANTE`;
- `AJUSTE_EFECTIVO_COBRADOR`;
- corrección auditada del conteo.

Nunca se modifican pagos de clientes para cuadrar caja.

Estados finales de `EntregaEfectivo`:

- `PENDIENTE_RECEPCION`
- `CONFIRMADA`
- `CONFIRMADA_CON_DIFERENCIA`
- `CANCELADA`

(`CON_DIFERENCIA` deja de usarse como estado persistido.)

## D-48 — Tipos de ContraparteExterna

Complementa D-34. Tipos canónicos:

- `APORTANTE`
- `PROVEEDOR`
- `CLIENTE` (exige `cliente_id` + snapshots)
- `INSTITUCION_FINANCIERA_EXTERNA`
- `OTRO`

## D-49 — Medio RETENIDO_DESEMBOLSO

Catálogo inicial de medios:

- `EFECTIVO`
- `TRANSFERENCIA`
- `RETENIDO_DESEMBOLSO`

`PRIMER_PAGO_RETENIDO` crea un `Pago` con `medio = RETENIDO_DESEMBOLSO`.

## D-50 — Motivo del fondo al cobrador

Se elimina `OrigenComercialFondo` del flujo ordinario.

La entrega al cobrador siempre mueve:

```text
CAJA_CENTRAL      - importe
EFECTIVO_COBRADOR + importe
```

Motivos de entrega:

- `FONDO_INICIAL`
- `FONDO_ADICIONAL`
- `PARA_DESEMBOLSO`
- `OPERACION_GENERAL`
- `OTRO`

Puede existir `operacion_relacionada_id` opcional.

Las aportaciones, recuperaciones e ingresos extraordinarios entran primero a
custodia real (`CAJA_CENTRAL` o `CUENTA_BANCARIA`) mediante sus propias
operaciones; el fondo al cobrador sale siempre de `CAJA_CENTRAL` (coherente
con D-25).

## D-51 — Puesta en marcha y carga inicial

Complementa D-42.

`CARGA_INICIAL_SALDO` se utiliza exclusivamente durante la puesta en marcha
para registrar saldos que ya existían antes de operar CREDIMEX.

Cada operación debe registrar:

- cuenta operativa;
- importe en centavos;
- fecha y hora de corte;
- responsable del conteo o validación;
- administrador;
- motivo;
- evidencia obligatoria;
- referencia de lote;
- idempotencia;
- auditoría.

Puede afectar:

- `CAJA_CENTRAL`;
- `EFECTIVO_COBRADOR`;
- `CUENTA_BANCARIA`;
- otras cuentas autorizadas en una migración documentada.

Reglas:

- una operación por cuenta y saldo cargado;
- varias operaciones pueden compartir referencia de lote;
- no representa una aportación nueva;
- no puede usarse para corregir errores posteriores;
- queda bloqueada después del primer cierre de fecha operativa;
- las correcciones posteriores requieren un ajuste administrativo;
- antes del cierre de la puesta en marcha se concilian saldos contra
  conteos físicos y estados de cuenta.

Proceso: `docs/03-processes/puesta-en-marcha-carga-inicial.md`.

## D-52 — Ajustes de efectivo

`AJUSTE_EFECTIVO_COBRADOR` y `AJUSTE_CAJA_CENTRAL` son **exclusivos del
administrador**.

El supervisor puede:

- registrar la incidencia;
- documentar la diferencia;
- adjuntar evidencia;
- solicitar el ajuste;
- darle seguimiento.

El supervisor **no** puede:

- aprobar el ajuste;
- ejecutar `MovimientoCuenta`;
- modificar directamente saldos proyectados.

Todo ajuste requiere:

- incidencia;
- motivo;
- administrador;
- importe;
- cuenta afectada;
- evidencia u observación;
- auditoría completa.

## D-53 — Días festivos

Al agregar o retirar un festivo **futuro**:

- mostrar vista previa de impacto;
- recalcular solamente cuotas `PROGRAMADA`;
- conservar número, orden e importes de cuotas;
- ajustar fechas futuras y fecha final;
- auditar valores anteriores y nuevos;
- recalcular proyecciones relacionadas.

Para la **fecha actual o fechas pasadas**:

- no regenerar automáticamente cuotas `PENDIENTE`,
  `PARCIALMENTE_CUBIERTA`, `CUBIERTA` o históricas;
- no modificar pagos, atrasos o semáforos anteriores;
- el cambio puede aplicar a nuevos calendarios;
- cualquier corrección histórica requiere proceso administrativo
  extraordinario y auditado.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`
- `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md`
- `docs/04-database/catalogo-operaciones-financieras.md`
- `docs/04-database/catalogo-entidades.md`
- `docs/04-database/catalogo-estados.md`
- `docs/04-database/modelo-movimientos-financieros.md`
- `docs/02-use-cases/UC-27-excepcion-permanencia-efectivo.md`
- `docs/03-processes/puesta-en-marcha-carga-inicial.md`
- `docs/01-requirements/collector-workday.md`
- `docs/01-requirements/cash-differences.md`
