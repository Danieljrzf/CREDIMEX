# CREDIMEX — Jornada del cobrador

**Estado:** Aprobado  
**Relación:** Complementa `CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`,
`CREDIMEX_Decisiones_Resueltas_v1.3.md` (D-14, D-15, D-18) y
`CREDIMEX_Decisiones_Modelo_Datos_v1.4.md` / `v1.5.md` (D-22, D-38, D-46).

---

## Propósito

Definir el ciclo de vida de la **jornada del cobrador**, que agrupa las
operaciones financieras de un cobrador durante una fecha operativa y sirve de
base para el cálculo del efectivo esperado y el corte.

## Identidad de la jornada

La jornada se identifica de forma única por:

```text
jornada = (cobrador, fecha operativa)
```

No pueden existir dos jornadas simultáneas para el mismo cobrador y la misma
fecha operativa.

## Apertura y cierre

Conforme a las decisiones D-18 y D-22:

- La jornada se **abre con la primera operación financiera** del cobrador en
  la fecha operativa.
- La fila nace directamente en `ABIERTA` (creación perezosa).
- `PENDIENTE` es **conceptual** y **no se persiste**.
- La jornada se **cierra mediante corte**.

## Estados persistidos

### ABIERTA

La jornada tiene operaciones financieras en curso. Se aceptan pagos,
comisiones, desembolsos, gastos autorizados y entregas de efectivo. El
efectivo esperado se actualiza con cada movimiento.

Transición: al iniciarse el proceso de corte pasa a `EN_CORTE`.

### EN_CORTE

El corte del cobrador está en proceso. Se cuenta el efectivo, se calcula la
diferencia y se define el dinero entregado y el conservado. Durante este
estado no se aceptan nuevas operaciones financieras ordinarias de la jornada.

Transiciones:

- al confirmarse el corte pasa a `CERRADA`;
- si el corte se cancela antes de confirmarse, regresa a `ABIERTA`.

### CERRADA

El corte fue confirmado. La jornada queda conciliada, se bloquean las
modificaciones ordinarias y se define el saldo inicial de la jornada
siguiente conforme a `cash-differences.md`.

Transición: solo mediante reapertura autorizada pasa a `REABIERTA`.

### REABIERTA

Un supervisor o administrador reabrió una jornada previamente cerrada, con
motivo, usuario, fecha y hora registrados. Se permiten reversos y ajustes
autorizados. Las versiones anteriores del corte se conservan.

Transición: al volver a confirmar el corte regresa a `CERRADA`.

## Diagrama de estados

```text
(primera operación financiera)
   │
   ▼
ABIERTA ◄──────────────┐
   │  (inicia corte)    │ (corte cancelado)
   ▼                    │
EN_CORTE ───────────────┘
   │  (corte confirmado)
   ▼
CERRADA
   │  (reapertura autorizada)
   ▼
REABIERTA
   │  (corte reconfirmado)
   ▼
CERRADA
```

## Permanencia nocturna de efectivo

- Máximo ordinario: **tres noches** consecutivas con efectivo conservado
  mayor a cero (D-14).
- La **cuarta noche** queda bloqueada por defecto (D-15).
- Solo el administrador puede autorizar una cuarta noche mediante
  `ExcepcionPermanenciaEfectivo` (D-38, D-46, UC-27).
- No se permite una quinta noche.
- No hay prórroga automática.
- La excepción no altera el disponible del cobrador; solo desbloquea la
  conservación en el corte hasta el importe autorizado.
- Si la excepción vence (`VENCIDA`), se genera incidencia y se bloquea una
  nueva permanencia.

## Reglas

- Una operación financiera ordinaria solo puede registrarse en una jornada
  `ABIERTA` (o `REABIERTA` para ajustes/reversos autorizados).
- No se registran pagos en una jornada `EN_CORTE`, `CERRADA` ni en una fecha
  ya conciliada, salvo reapertura autorizada.
- El saldo inicial de la jornada siguiente proviene del efectivo conservado y
  autorizado en el corte, no del efectivo esperado teórico.
- Toda transición de estado y toda reapertura generan auditoría.
- El corte valida que el efectivo conservado no exceda el importe de una
  excepción de cuarta noche vigente.
