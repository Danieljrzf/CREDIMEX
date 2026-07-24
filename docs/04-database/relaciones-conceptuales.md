# CREDIMEX — Relaciones conceptuales

**Estado:** Aprobado (Fase 3A)  
**Alcance:** Cardinalidades, tablas intermedias y restricciones lógicas.
Sin SQL.

---

## 1. Identidad

```text
Usuario N ── 1 Rol
Rol N ── M Permiso                 vía RolPermiso
Usuario 1 ── N Dispositivo
Usuario 1 ── N SesionToken
Usuario(cobrador) 1 ── 1 CuentaOperativa(EFECTIVO_COBRADOR)
```

**Restricciones:**

- Un usuario tiene un solo rol principal en V1 (D-27).
- `EFECTIVO_COBRADOR` pertenece al usuario cobrador, no a la jornada (D-32).

---

## 2. Cliente, domicilio y ruta

```text
Cliente 1 ── N ContactoAlternativo
Cliente 1 ── N Referencia
Cliente 1 ── N DomicilioUbicacion          (1 vigente)
Cliente 1 ── N DocumentoCliente
Cliente 1 ── N ConfirmacionNoDuplicado
Cliente 1 ── N AsignacionClienteRuta       (1 vigente)
Ruta 1 ── N AsignacionClienteRuta
Ruta 1 ── N AsignacionRutaCobrador         (1 vigente)
Cliente / Credito ── N AsignacionTemporalCobranza
```

**Restricciones:**

- No existe `Cliente.ruta_actual` como segunda fuente de verdad.
- Domicilio y GPS vigentes viven en `DomicilioUbicacion`, no duplicados en
  `Cliente`.
- Un crédito solo puede estar habilitado para un cobrador a la vez
  (titular vía ruta o asignación temporal vigente).

---

## 3. Solicitud y crédito

```text
Cliente 1 ── N SolicitudCredito
SolicitudCredito 1 ── N Autorizacion
SolicitudCredito (APROBADA) 1 ── crea ── 1 Credito (PENDIENTE_DESEMBOLSO)

Cliente 1 ── N Credito
Credito 1 ── N VersionCondicionesCredito
Credito 1 ── N Calendario                  (1 VIGENTE)
Calendario 1 ── N CuotaProgramada
Credito 1 ── 1 CuentaOperativa(SALDO_CREDITO)
```

**Restricciones:**

- Solicitud `APROBADA` es terminal y crea exactamente un crédito pendiente de
  desembolso.
- La solicitud no tiene estado `DESEMBOLSADA`.
- `CANCELADO` del crédito solo desde `PENDIENTE_DESEMBOLSO` (D-23).
- Máximo de cinco: créditos `ACTIVO` con `saldo_actual_centavos > 0`.

---

## 4. Desembolso (1:N con máximo un confirmado)

```text
Credito 1 ── N Desembolso
Desembolso 0..1 ── 1 ReservaEfectivo       (mientras PENDIENTE_CONFIRMACION)
Desembolso (CONFIRMADO) 1 ── 1 Comision (LIQUIDADA)
Desembolso (CONFIRMADO) 1 ── 1 OperacionFinanciera
```

**Restricciones lógicas (D-31):**

- Puede haber varios desembolsos `CANCELADO_ANTES_DE_ENTREGA`.
- **Máximo un** desembolso `CONFIRMADO` por crédito.
- Sin reverso ordinario del confirmado; solo
  `AJUSTE_ADMINISTRATIVO_DESEMBOLSO` (D-30).

---

## 5. Pagos, cuotas y tickets

```text
Credito 1 ── N Pago
Pago N ── M CuotaProgramada                vía AplicacionPagoCuota
Pago 1 ── 0..1 ReversoPago
Pago 1 ── 0..N Ticket
Ticket 1 ── N ReimpresionTicket
Credito 1 ── N TransferenciaBancaria       (genera pago / reducción de saldo)
TransferenciaBancaria N ── 1 CuentaReceptora
CuentaReceptora 1 ── 1 CuentaOperativa(CUENTA_BANCARIA)
```

**Restricciones:**

- FIFO: aplicaciones de la cuota más antigua vencida a la más nueva.
- Cuota parcialmente cubierta permanece `PARCIALMENTE_CUBIERTA`.
- Transferencia no mueve `EFECTIVO_COBRADOR`.

---

## 6. Renovación, reestructura y castigo

```text
Credito(LIQUIDADO) 1 ── 1 Renovacion ── 1 Credito(nuevo)
Credito 1 ── N Reestructuracion
Reestructuracion ── crea VersionCondicionesCredito + Calendario(VIGENTE)
                    y marca Calendario anterior REEMPLAZADO
                    y cuotas vigentes CANCELADA_POR_REESTRUCTURA

Credito 1 ── 0..1 CastigoCredito           (snapshot de saldo)
Credito(CASTIGADO) 1 ── N RecuperacionCreditoCastigado
Cliente 1 ── N RestriccionCliente
```

**Restricciones (D-24):**

- Recuperación reduce `SALDO_CREDITO`; no reactiva, no cuenta en máx. 5, no
  retira restricción, no habilita renovación.

---

## 7. Jornada, caja central y entregas

```text
Usuario(cobrador) 1 ── N JornadaCobrador
  UK lógica: (cobrador_id, fecha_operativa)

CajaCentral 1 ── 1 CuentaOperativa(CAJA_CENTRAL)
CajaCentral 1 ── N JornadaCajaCentral

JornadaCobrador 1 ── N OperacionFinanciera (del día)
JornadaCobrador 1 ── N CorteCobrador       (versiones)
JornadaCajaCentral 1 ── N CorteCajaCentral

EntregaEfectivo 1 ── 1 OperacionFinanciera (al confirmar)
EntregaEfectivo 0..N ── IncidenciaCaja
Corte 0..N ── IncidenciaCaja
```

**Restricciones:**

- V1: una `CajaCentral` activa (D-29); supervisor/admin sin caja personal.
- Entrega ordinaria al cobrador sale de `CAJA_CENTRAL` (D-25).
- Orígenes comerciales abstractos no cargan `EFECTIVO_COBRADOR` directo.
- Jornada nace `ABIERTA` de forma perezosa (D-22).

---

## 8. Libro operativo

```text
OperacionFinanciera 1 ── N MovimientoCuenta
MovimientoCuenta N ── 1 CuentaOperativa

CuentaOperativa.tipos:
  EFECTIVO_COBRADOR  → Usuario cobrador
  CAJA_CENTRAL       → CajaCentral
  CUENTA_BANCARIA    → CuentaReceptora
  SALDO_CREDITO      → Credito
```

**Restricciones:**

- Toda afectación de saldo proyectado requiere `MovimientoCuenta` en la misma
  transacción.
- Transferencias internas: ≥2 movimientos, mismo folio de operación.
- Signo: positivo aumenta; negativo disminuye.

---

## 9. Tablas intermedias / de enlace

| Enlace | Une | Nota |
|---|---|---|
| `RolPermiso` | Rol ↔ Permiso | N:M |
| `AplicacionPagoCuota` | Pago ↔ CuotaProgramada | importe aplicado |
| `AsignacionRutaCobrador` | Ruta ↔ Usuario | historial de titular |
| `AsignacionClienteRuta` | Cliente ↔ Ruta | historial de pertenencia |
| `AsignacionTemporalCobranza` | Cobranza temporal | no polimórfica genérica |
| `Renovacion` | Credito ↔ Credito | origen liquidado → nuevo |
| `OperacionFinanciera` | Dominio ↔ Movimientos | folio común |
| `IdempotenciaOperacion` | Clave ↔ resultado | reintentos |

---

## 10. Diagrama resumido del núcleo financiero

```text
Solicitud --APROBADA--> Credito(PENDIENTE_DESEMBOLSO)
                              |
                         Desembolso* (1:N, ≤1 CONFIRMADO)
                              |
                              v
                        Credito(ACTIVO)
                              |
              +---------------+---------------+
              |               |               |
          Calendario      Pago/Appl.     Reestructuracion
          + Cuotas        + Ticket       + nuevo Calendario
              |
         proyección atraso (caché)
```
