# CREDIMEX — Decisiones de Modelo de Datos

**Versión:** 1.4  
**Fecha:** 24 de julio de 2026  
**Estado:** Aprobado  
**Relación:** Complementa `CREDIMEX_Decisiones_Resueltas_v1.3.md` y el modelo
conceptual en `docs/04-database/`

---

## Propósito

Documentar las decisiones D-21 a D-32 que cierran el modelo conceptual de datos
antes del ERD físico. No sustituyen el documento maestro ni las decisiones v1.3;
las precisan para diseño de información.

---

## Naturaleza del libro operativo

El libro formado por `OperacionFinanciera`, `CuentaOperativa` y `MovimientoCuenta`
es un **libro operativo** de CREDIMEX.

- Permite reconstruir saldos de efectivo, caja central, cuentas bancarias
  registradas y saldo de crédito.
- **No** sustituye una contabilidad fiscal completa.
- Las transferencias internas generan movimientos en ambas cuentas afectadas.
- Las entradas o salidas externas generan movimiento sobre la cuenta de custodia
  real, acompañado de contraparte conceptual, origen comercial, documento y
  motivo cuando corresponda.

## Convención de signos

En `MovimientoCuenta` y en las proyecciones de saldo:

- un importe **positivo** aumenta el saldo de la `CuentaOperativa`;
- un importe **negativo** disminuye el saldo de la `CuentaOperativa`.

---

## D-21 — Cuenta SALDO_CREDITO

La `CuentaOperativa` de tipo `SALDO_CREDITO` puede crearse al crear el crédito,
con saldo cero.

Se carga **únicamente** al confirmar el desembolso:

```text
SALDO_CREDITO + total_a_pagar
total_a_pagar = monto + interés
```

La comisión no forma parte del saldo.

## D-22 — Jornada

`PENDIENTE` es **conceptual** y **no se persiste**.

La fila de `JornadaCobrador` nace en `ABIERTA` con la primera operación
financiera del día (incluida la recepción de un fondo).

Estados persistidos:

- `ABIERTA`
- `EN_CORTE`
- `CERRADA`
- `REABIERTA`

Debe existir unicidad lógica por cobrador y fecha operativa.

## D-23 — Cancelación del crédito

`CANCELADO` solo es posible desde `PENDIENTE_DESEMBOLSO` y **antes** de
confirmar el desembolso.

Un crédito `ACTIVO` no puede cancelarse mediante flujo ordinario.

## D-24 — Recuperación post-castigo

Se mantiene la misma `CuentaOperativa` `SALDO_CREDITO`.

`CastigoCredito` guarda un **snapshot** del saldo al castigar.

Las recuperaciones reducen `SALDO_CREDITO`, pero:

- no reactivan el crédito;
- no cuentan dentro del máximo de cinco;
- no retiran automáticamente la restricción;
- no habilitan renovación.

## D-25 — Orígenes externos

`APORTACION_REGISTRADA`, `RECUPERACION` e `INGRESO_EXTRAORDINARIO_AUTORIZADO`
son **tipos de origen comercial**.

El dinero debe entrar primero a una cuenta real de custodia:

- `CAJA_CENTRAL`
- `CUENTA_BANCARIA`

Una entrega ordinaria al cobrador debe salir de `CAJA_CENTRAL`.

No se puede aumentar `EFECTIVO_COBRADOR` directamente desde un origen abstracto.

## D-26 — Reserva de efectivo

Un desembolso en `PENDIENTE_CONFIRMACION` crea una **reserva de efectivo**.

La reserva:

- reduce el disponible;
- no genera todavía `MovimientoCuenta` definitivo;
- se libera al cancelar el intento;
- se consume al confirmar.

Se modela conceptualmente como `ReservaEfectivo` (o mecanismo equivalente).

## D-27 — Roles y permisos

Cada `Usuario` tiene un solo `Rol` principal en V1.

Se conservan:

- `Rol`
- `Permiso`
- `RolPermiso`

Relaciones:

```text
Usuario N:1 Rol
Rol N:M Permiso
```

## D-28 — Proyección de atraso

En la V1, `Credito` tendrá proyecciones:

- `dias_atraso_actual`
- `semaforo_actual`
- `fecha_calculo_atraso`
- `cuotas_vencidas_pendientes`

Son caché reconstruible, no fuente definitiva.

Deben recalcularse después de pagos, reversos, reestructuras, cambios de
calendario y proceso diario.

## D-29 — Caja central

`CajaCentral` tendrá:

- `codigo`
- `nombre`
- `estado`

En V1 solo habrá una activa.

No se agrega todavía `sucursal_id`.

## D-30 — Ajuste excepcional de desembolso

Tipo de `OperacionFinanciera`:

```text
AJUSTE_ADMINISTRATIVO_DESEMBOLSO
```

Solo administrador.

Requiere:

- incidencia;
- motivo;
- evidencia u observación;
- movimientos compensatorios;
- auditoría completa.

No elimina ni revierte físicamente el desembolso confirmado.

## D-31 — Intentos de desembolso

Relación:

```text
Credito 1:N Desembolso
```

Puede haber varios intentos cancelados, pero **máximo un desembolso
`CONFIRMADO`** por crédito.

## D-32 — Cuenta del cobrador

`EFECTIVO_COBRADOR` pertenece al `Usuario` cobrador, no a `JornadaCobrador`.

`JornadaCobrador` agrupa las operaciones por fecha operativa.

---

## Referencias

- `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md` (D-01 a D-20)
- `docs/04-database/catalogo-entidades.md`
- `docs/04-database/modelo-movimientos-financieros.md`
- `docs/01-requirements/collector-workday.md`
- `docs/01-requirements/cash-differences.md`
