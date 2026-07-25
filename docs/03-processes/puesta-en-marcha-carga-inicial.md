# CREDIMEX — Puesta en marcha y carga inicial de saldos

**Estado:** Aprobado (Fase 3A)  
**Relación:** D-42, D-51; `catalogo-operaciones-financieras.md`;
`modelo-movimientos-financieros.md`.

---

## Objetivo

Registrar los saldos de efectivo y cuentas bancarias que **ya existían**
antes de operar CREDIMEX, mediante `CARGA_INICIAL_SALDO`, sin confundirlos
con aportaciones nuevas ni con correcciones posteriores.

## Responsables

| Rol | Responsabilidad |
|---|---|
| Administrador | Captura y confirma cada `CARGA_INICIAL_SALDO`; cierra la puesta en marcha |
| Responsable del conteo o validación | Realiza o certifica el conteo físico / estado de cuenta |
| Supervisor | Puede apoyar en conteos y documentación; no ejecuta la carga |

## Precondiciones

- CREDIMEX aún no ha cerrado su primera fecha operativa.
- Existen las `CuentaOperativa` a cargar (`CAJA_CENTRAL`,
  `EFECTIVO_COBRADOR`, `CUENTA_BANCARIA` u otras autorizadas en una
  migración documentada).
- Hay conteos físicos o estados de cuenta de referencia.
- El administrador tiene permiso y sesión activa.
- Existe conexión con el servidor.

## Preparación

1. Definir la **fecha y hora de corte** del inventario de saldos.
2. Asignar un **lote** (`referencia_lote`) que agrupe todas las cargas de la
   puesta en marcha.
3. Elaborar el listado de cuentas e importes a cargar (centavos).
4. Designar el responsable del conteo o validación por cuenta o grupo.
5. Recopilar evidencias (actas de conteo, fotografías, estados de cuenta).

## Captura

Para cada cuenta y saldo:

1. El administrador selecciona `CARGA_INICIAL_SALDO`.
2. Captura:
   - cuenta operativa;
   - importe en centavos;
   - fecha y hora de corte;
   - responsable del conteo o validación;
   - motivo;
   - referencia de lote;
   - clave de idempotencia.
3. Confirma. El sistema crea la `OperacionFinanciera`, el
   `MovimientoCuenta`, actualiza la proyección de saldo y registra
   auditoría.

Reglas de captura:

- **una operación por cuenta y saldo cargado**;
- varias operaciones pueden compartir la misma `referencia_lote`;
- no representa una aportación nueva.

## Evidencia

Cada carga exige **evidencia obligatoria**. La evidencia queda ligada a la
operación vía `EvidenciaOperacion` (`tipo_sustento = CON_DOCUMENTO`).

## Validaciones

Antes de confirmar cada operación, el sistema valida:

- que la puesta en marcha no esté cerrada (aún no hay primer cierre de
  fecha operativa);
- que la cuenta exista y esté autorizada para carga inicial;
- importe, motivo, responsable del conteo, fecha/hora de corte y lote
  presentes;
- idempotencia (reintento no duplica movimiento);
- evidencia adjunta.

## Conciliación

Antes del cierre de la puesta en marcha:

1. Comparar saldos cargados contra conteos físicos (efectivo) y estados de
   cuenta (bancos).
2. Verificar que la suma por lote coincida con el inventario de referencia.
3. Documentar y resolver diferencias detectadas antes del cierre.

## Cierre de puesta en marcha

1. El administrador confirma que todas las cuentas previstas están cargadas
   y conciliadas.
2. Se ejecuta el **primer cierre de fecha operativa** (corte de jornada /
   caja según corresponda).
3. A partir de ese momento, `CARGA_INICIAL_SALDO` queda **bloqueada**.
4. Queda registro auditado del lote, operaciones y cierre.

## Errores y correcciones

| Situación | Tratamiento |
|---|---|
| Error detectado **antes** del primer cierre, operación aún no confirmada | Corregir captura o cancelar el intento; no genera movimiento |
| Error detectado **después** del primer cierre | **Prohibido** `CARGA_INICIAL_SALDO`; usar ajuste administrativo documentado (`AJUSTE_EFECTIVO_COBRADOR`, `AJUSTE_CAJA_CENTRAL` u otro ajuste autorizado) |

`CARGA_INICIAL_SALDO` no puede usarse para corregir errores posteriores al
cierre de la puesta en marcha.

## Criterios de aceptación

- Cada cuenta cargada tiene una operación con folio, idempotencia, evidencia,
  motivo, responsable del conteo, administrador, fecha/hora de corte y lote.
- Varias operaciones del mismo lote son consultables por `referencia_lote`.
- Los saldos proyectados coinciden con la suma de movimientos de carga.
- Tras el primer cierre de fecha operativa, el sistema rechaza nuevas
  `CARGA_INICIAL_SALDO`.
- Una corrección posterior usa ajuste administrativo, no carga inicial.
- La operación no se confunde con `APORTACION_CAPITAL`.

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md` (D-42, D-51)
- `docs/04-database/catalogo-operaciones-financieras.md`
- `docs/04-database/modelo-movimientos-financieros.md`
- `docs/02-use-cases/UC-25-caja-central.md`
