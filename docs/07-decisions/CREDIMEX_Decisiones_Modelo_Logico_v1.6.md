# CREDIMEX — Decisiones de Modelo Lógico

**Versión:** 1.6
**Fecha:** 25 de julio de 2026
**Estado:** Aprobado
**Alcance:** Modelo lógico (Fase 3A.2). Sin tipos SQL. Sin migraciones.
**Relación:** Complementa D-01 a D-53. No las reemplaza ni modifica.

---

## Propósito

Documentar las decisiones D-54 a D-68 que cierran el inventario lógico,
las fusiones de tesorería, la idempotencia previa a folio, el lote de carga
inicial, las asignaciones temporales tipadas, las autorizaciones de crédito,
el dueño exclusivo de `CuentaOperativa`, la auditoría lógica, la comisión
ligada al desembolso, las resoluciones de incidencia, las cuentas bancarias
propias y los ámbitos de idempotencia.

Entregables asociados:

- `docs/04-database/inventario-tablas-logicas.md`
- diccionarios por dominio;
- `docs/04-database/claves-relaciones-restricciones.md`
- `docs/04-database/indices-conceptuales.md`
- `docs/04-database/erd-conceptual.md`
- `docs/04-database/riesgos-modelo-logico.md`

---

## D-54 — Tesorería

Se crea una sola tabla de detalle de dominio:

- `operaciones_tesoreria`

para:

- `APORTACION_CAPITAL`;
- `INGRESO_EXTRAORDINARIO`;
- `DEPOSITO_BANCARIO_MANUAL`;
- `RETIRO_BANCARIO_A_CAJA_CENTRAL`;
- `RETIRO_CAJA_CENTRAL`;
- `GASTO_CAJA_CENTRAL`.

Las afectaciones reales de saldo se registran mediante `MovimientoCuenta`.

Se mantienen tablas separadas:

- `entregas_efectivo`;
- `gastos_ruta`;
- `incidencias_caja`;
- `cortes_cobrador`;
- `cortes_caja_central`.

Se eliminan como candidatas individuales:

- `aportaciones`;
- `ingresos_extraordinarios`;
- `depositos_bancarios_manuales`;
- `retiros`.

La entidad conceptual `Gasto` del catálogo se materializa como `gastos_ruta`
(gasto de ruta del cobrador), distinta de `GASTO_CAJA_CENTRAL`.

---

## D-55 — Idempotencia

Se conserva la tabla:

- `idempotencias_operacion`

Debe controlar solicitudes **antes** de que exista una `OperacionFinanciera`.

Conceptualmente:

| Campo | Significado |
|---|---|
| `clave` | Clave de idempotencia del cliente |
| `ambito` | Comando funcional (D-68) |
| `huella_solicitud` | Resumen del payload relevante |
| `estado` | `EN_PROCESO`, `COMPLETADA`, `FALLIDA` |
| `operacion_financiera_id` | Opcional; se llena al completar |
| `referencia_resultado` | Referencia del resultado cacheado |
| fechas | Creación, actualización, finalización |
| `error_controlado` | Mensaje/código si `FALLIDA` |

Unicidad conceptual: (`ambito`, `clave`).

---

## D-56 — Lote de carga inicial

Se crea formalmente:

- `lotes_carga_inicial`

Relación:

```text
LoteCargaInicial 1 ── N OperacionFinanciera
```

Toda `CARGA_INICIAL_SALDO` pertenece a un lote.

Estados del lote:

- `ABIERTO`;
- `EN_VALIDACION`;
- `CERRADO`;
- `CANCELADO`.

Una cuenta operativa no puede aparecer más de una vez en el mismo lote.

---

## D-57 — Asignaciones temporales

Se mantiene la cabecera:

- `asignaciones_temporales_cobranza`

Se crean detalles:

- `asignaciones_temporales_rutas`;
- `asignaciones_temporales_clientes`;
- `asignaciones_temporales_creditos`.

Una cabecera utiliza **un solo** tipo de alcance y puede contener uno o
varios elementos de ese tipo.

No se usan `cliente_id`, `credito_id` y `ruta_id` opcionales en la misma
tabla de cabecera.

Complemento de discriminator y reglas de creación: D-67.

---

## D-58 — Autorizaciones de crédito

Se sustituye la candidata `autorizaciones` por:

- `autorizaciones_credito`

Relación exclusiva:

```text
SolicitudCredito 1 ── N AutorizacionCredito
```

Las autorizaciones de gastos, reestructuraciones, excepciones y ajustes
permanecen en sus entidades correspondientes (campos de autorizador /
resultado en esas tablas), no en `autorizaciones_credito`.

---

## D-59 — Propietario exclusivo de CuentaOperativa

`cuentas_operativas` tiene referencias exclusivas posibles a:

- `usuario_cobrador_id`;
- `caja_central_id`;
- `cuenta_bancaria_id`;
- `credito_id`.

Exactamente una debe estar informada.

El tipo de cuenta debe coincidir con su propietario:

| Tipo | Propietario |
|---|---|
| `EFECTIVO_COBRADOR` | usuario cobrador |
| `CAJA_CENTRAL` | caja central |
| `CUENTA_BANCARIA` | cuenta bancaria propia |
| `SALDO_CREDITO` | crédito |

Unicidades conceptuales:

- un `EFECTIVO_COBRADOR` activo por cobrador;
- un `SALDO_CREDITO` por crédito;
- un `CAJA_CENTRAL` por caja;
- un `CUENTA_BANCARIA` por cuenta bancaria propia.

---

## D-60 — Auditoría con referencia lógica

`eventos_auditoria` utiliza:

- `entidad_tipo`;
- `entidad_id`;

como referencia lógica (sin FK polimórfica física única).

Se agrega `operacion_financiera_id` opcional con FK real cuando corresponda.

La aplicación valida la existencia de la entidad referenciada.

---

## D-61 — Comisión ligada al desembolso

Relación:

```text
Desembolso 1 ── 0..1 Comision
```

- Un intento cancelado no tiene comisión liquidada.
- Un desembolso `CONFIRMADO` debe tener exactamente una comisión.
- `comisiones.desembolso_id` es único.
- No se relaciona la comisión directamente como 1:1 con el crédito.

---

## D-62 — Resoluciones de incidencia de caja

Se crea:

- `resoluciones_incidencia_caja`

Relaciones:

```text
IncidenciaCaja 1 ── N ResolucionIncidenciaCaja
OperacionFinanciera 1 ── 0..1 ResolucionIncidenciaCaja
```

Admite resoluciones parciales y registra importe aplicado, tipo, fecha,
usuario y observaciones.

Esta tabla es la relación oficial entre incidencias y operaciones de
resolución.

Complemento de cierre: D-65.

---

## D-63 — Cuentas bancarias propias

Se sustituye la candidata `cuentas_receptoras` por:

- `cuentas_bancarias`

Representa cuentas propias de CREDIMEX.

Usos iniciales:

- `RECEPCION_PAGOS`;
- `TESORERIA`;
- `AMBOS`.

Una cuenta propia no se representa como `ContraparteExterna`.

---

## D-64 — Detalle de tesorería obligatorio

Relación general:

```text
OperacionFinanciera 1 ── 0..1 OperacionTesoreria
```

Para estos tipos debe existir **exactamente una** fila en
`operaciones_tesoreria`:

- `APORTACION_CAPITAL`;
- `INGRESO_EXTRAORDINARIO`;
- `DEPOSITO_BANCARIO_MANUAL`;
- `RETIRO_BANCARIO_A_CAJA_CENTRAL`;
- `RETIRO_CAJA_CENTRAL`;
- `GASTO_CAJA_CENTRAL`.

`operaciones_tesoreria.operacion_financiera_id` es único.

La operación, su detalle y sus movimientos se crean en la misma
transacción.

Ningún otro tipo puede utilizar `operaciones_tesoreria`.

Los saldos solo cambian mediante `MovimientoCuenta`.

---

## D-65 — Cierre de incidencias

Las resoluciones pueden ser parciales.

Se calculan (proyectados / derivados):

- `importe_original`;
- `importe_resuelto`;
- `importe_pendiente`.

Una incidencia solo puede pasar a `RESUELTA` cuando:

- `importe_pendiente = 0`;
- y existe confirmación explícita de cierre.

Supervisor o administrador pueden cerrar cuando las resoluciones sean
entregas o reposiciones normales.

Si existe `AJUSTE_EFECTIVO_COBRADOR` o `AJUSTE_CAJA_CENTRAL` entre las
resoluciones, solo el administrador puede cerrar.

Cada resolución es append-only.

No se permite que una resolución supere el importe pendiente.

Reabrir una incidencia resuelta requiere administrador, motivo y auditoría.

---

## D-66 — Gasto de ruta

El código canónico de `OperacionFinanciera` es:

- `GASTO_RUTA`

Para una operación `GASTO_RUTA` debe existir exactamente una fila en
`gastos_ruta`.

Relaciones:

```text
OperacionFinanciera 1 ── 0..1 GastoRuta
JornadaCobrador 1 ── N GastoRuta
```

`gastos_ruta` conserva los datos del proceso; `MovimientoCuenta` registra la
afectación al efectivo.

---

## D-67 — Asignaciones temporales (discriminator y integridad)

El discriminator se llama:

- `tipo_alcance`

Valores:

- `RUTA`;
- `CLIENTE`;
- `CREDITO`.

Reglas:

- la cabecera y sus detalles se crean en la misma transacción;
- toda cabecera debe contener al menos un detalle;
- una cabecera solo puede utilizar la tabla de detalle correspondiente a su
  `tipo_alcance`;
- no se persisten cabeceras vacías;
- no se permiten duplicados dentro de la asignación ni coberturas vigentes
  que hagan que un crédito sea operable por dos cobradores al mismo tiempo.

---

## D-68 — Ámbitos de idempotencia

`ambito` representa un **comando funcional**, no una URL ni un endpoint.

Catálogo inicial:

| Código |
|---|
| `CONFIRMAR_DESEMBOLSO` |
| `REGISTRAR_PRIMER_PAGO_RETENIDO` |
| `REGISTRAR_PAGO` |
| `REVERSAR_PAGO` |
| `REESTRUCTURAR_CREDITO` |
| `REGISTRAR_RECUPERACION_CASTIGADO` |
| `ENTREGAR_EFECTIVO_A_COBRADOR` |
| `ENTREGAR_EFECTIVO_A_CAJA_CENTRAL` |
| `REGISTRAR_GASTO_RUTA` |
| `REGISTRAR_REPOSICION_FALTANTE` |
| `AJUSTAR_EFECTIVO_COBRADOR` |
| `REGISTRAR_OPERACION_TESORERIA` |
| `AJUSTAR_CAJA_CENTRAL` |
| `AJUSTAR_DESEMBOLSO_ADMINISTRATIVAMENTE` |
| `CARGAR_SALDO_INICIAL` |

Unicidad: (`ambito`, `clave`).

Reglas de huella:

- misma clave y misma huella → devolver el resultado anterior;
- misma clave con huella diferente → error;
- el subtipo de tesorería forma parte de la huella de
  `REGISTRAR_OPERACION_TESORERIA`.

---

## Efecto en el inventario

| Acción | Tablas |
|---|---|
| Eliminadas como candidatas | `aportaciones`, `ingresos_extraordinarios`, `depositos_bancarios_manuales`, `retiros` |
| Fusionadas en | `operaciones_tesoreria` |
| Renombradas | `gastos` → `gastos_ruta`; `autorizaciones` → `autorizaciones_credito`; `cuentas_receptoras` → `cuentas_bancarias` |
| Nuevas | `operaciones_tesoreria`, `asignaciones_temporales_rutas`, `asignaciones_temporales_clientes`, `asignaciones_temporales_creditos`, `resoluciones_incidencia_caja`, `lotes_carga_inicial` |

**Conteo final de tablas candidatas:** 67.

Detalle: `docs/04-database/inventario-tablas-logicas.md`.

---

## Relación con D-01 a D-53

Estas decisiones **no alteran** D-01 a D-53.

Precisan únicamente la materialización lógica del catálogo conceptual
aprobado en v1.4/v1.5.

---

## Referencias

- `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md`
- `docs/04-database/catalogo-entidades.md`
- `docs/04-database/relaciones-conceptuales.md`
- `docs/04-database/modelo-movimientos-financieros.md`
- `docs/04-database/catalogo-operaciones-financieras.md`
