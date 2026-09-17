# CREDIMEX — Restricciones físicas PostgreSQL

**Estado:** Aprobado (Fase 3A.3)
**Decisiones:** D-75, D-76, D-81, D-82, D-89, D-91.
**Base:** `claves-relaciones-restricciones.md` (v1.6).
**Alcance:** Clasificación. Sin sintaxis SQL ejecutable.

---

## Clasificación

| Código | Significado |
|---|---|
| **DIR** | Restricción directa (PK, FK, UNIQUE, CHECK de una fila) |
| **PAR** | Índice único parcial |
| **APP** | Validación de aplicación |
| **TX** | Regla en la misma transacción |
| **REC** | Reconciliación / proceso posterior |

---

## 1. Directas (DIR)

| Regla | Mecanismo candidato |
|---|---|
| PK `id` en cada tabla | PRIMARY KEY |
| FK entre tablas | FK `NO ACTION` (predeterminado D-91); no diferible |
| UK jornadas cobrador (cobrador, fecha) | UNIQUE |
| UK jornadas caja (caja, fecha) | UNIQUE |
| UK folio operación | UNIQUE |
| UK idempotencia (`ambito`, `clave`) | UNIQUE |
| UK `comisiones.desembolso_id` | UNIQUE |
| UK detalle 1:1 OF (`operaciones_tesoreria`, `gastos_ruta`, resolución) | UNIQUE sobre `operacion_financiera_id` |
| UK `id_publico` (17 tablas) | UNIQUE |
| Dueño exclusivo `cuentas_operativas` | CHECK exactamente una FK de dueño |
| Tipo de cuenta ↔ dueño | CHECK |
| `operacion_padre_id <> id` | CHECK |
| `movimientos_cuenta.importe_centavos <> 0` | CHECK |
| Rangos GPS | CHECK |
| Códigos/estados técnicos cerrados | CHECK o FK a catálogo |
| (`sistema_origen`, `id_externo_origen`) únicos si ambos presentes | UNIQUE parcial o equivalente documentado (D-87) |

FK: sin CASCADE en hechos financieros. En RBAC V1, las FK de
`rol_permisos` utilizan `ON UPDATE NO ACTION` y
`ON DELETE NO ACTION`, no diferibles; la posibilidad de CASCADE de D-76
no se adopta. `SET NULL` solo aplica a relaciones opcionales no
financieras. `RESTRICT` solo donde se documente rechazo inmediato
(D-76, D-91).

---

## 2. Índices únicos parciales (PAR) — D-75

| Invariante | Condición parcial conceptual |
|---|---|
| Un calendario `VIGENTE` por crédito | `estado = VIGENTE` |
| Un desembolso `CONFIRMADO` por crédito | `estado = CONFIRMADO` |
| Una caja central `ACTIVA` (V1) | `estado = ACTIVA` |
| Una cuenta operativa activa por propietario | `activa` + dueño según tipo |
| Asignación ruta–cobrador vigente | `estado = VIGENTE` (una por ruta) |
| Asignación cliente–ruta vigente | `estado = VIGENTE` (una por cliente) |
| Otras asignaciones vigentes | Según diccionario / D-67 |

---

## 3. Aplicación (APP)

| Regla |
|---|
| Máximo cinco créditos `ACTIVO` con saldo > 0 |
| Un crédito operable por un solo cobrador a la vez |
| FIFO de aplicaciones a cuotas |
| Cierre de incidencia (pendiente = 0 + confirmación; roles D-65) |
| Resolución no supera pendiente |
| Huella de idempotencia (misma clave / huella distinta → error) |
| Tipos de tesorería ↔ fila en `operaciones_tesoreria` |
| `GASTO_RUTA` ↔ fila en `gastos_ruta` |
| Cabecera temporal: `tipo_alcance` y tabla de detalle coherentes |
| Existencia de entidad en auditoría lógica (`entidad_tipo`/`entidad_id`) |
| `CARGA_INICIAL_SALDO` bloqueada tras cierre de puesta en marcha |
| UUIDv7 generado solo en backend |
| Sanitización de auditoría (D-78) |

---

## 4. Transacción (TX)

| Regla |
|---|
| Orden de bloqueo D-81 |
| Movimiento + saldo + `version` en la misma TX |
| OF + detalle dominio + movimientos juntos (tesorería, gasto ruta, etc.) |
| Confirmación desembolso + comisión + consumo reserva |
| Pago + aplicaciones + ticket + proyecciones |
| Cabecera temporal + ≥1 detalle |
| Idempotencia `EN_PROCESO` → `COMPLETADA` con OF |
| Carga inicial: bloquear lote; validar no repetición cuenta (D-89) |

---

## 5. Reconciliación (REC)

| Regla |
|---|
| `saldo_actual` vs suma de `movimientos_cuenta` |
| Atraso / semáforo vs cuotas y fecha operativa |
| `importe_resuelto` / `importe_pendiente` vs resoluciones |
| Detección de dobles vigentes/confirmados |
| Lote carga: una cuenta no repetida en el mismo lote (D-89) |
| Suma de cuotas = `total_pagar_centavos` |
| Comisión fuera del saldo |

---

## 6. Carga inicial: lote + cuenta (D-89)

**No** es UNIQUE directo de base de datos.

**No** se agrega tabla adicional.

**No** se duplica `cuenta_operativa_id` en `operaciones_financieras`.

`lote_carga_inicial_id` es opcional en OF y **obligatorio** cuando el
tipo es `CARGA_INICIAL_SALDO`. Cada operación de carga afecta exactamente
una cuenta mediante su movimiento.

Garantía:

1. bloqueo de la fila del lote;
2. validación transaccional;
3. consulta de operaciones y movimientos existentes;
4. reconciliación.

**Clasificación: APP + TX + REC.**

---

## 7. Triggers

Ningún trigger de lógica financiera en V1 (D-82).

---

## Referencias

- `docs/04-database/claves-relaciones-restricciones.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
