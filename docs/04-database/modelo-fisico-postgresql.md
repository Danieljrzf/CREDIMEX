# CREDIMEX — Modelo físico preliminar PostgreSQL

**Estado:** Aprobado (Fase 3A.3)
**Fecha:** 26 de julio de 2026
**Alcance:** Tipos y políticas físicas. Sin SQL ejecutable. Sin
migraciones. Sin PostGIS. Sin triggers financieros.
**Decisiones:** D-69 a D-91.
**Base lógica:** 67 tablas (`inventario-tablas-logicas.md`).

---

## 1. Mapeo de tipos conceptuales → físicos

| Conceptual / uso | Tipo físico candidato | Notas |
|---|---|---|
| Identificador interno (PK/FK) | `BIGINT` | PK: identidad servidor (D-69) |
| Identificador público | `UUID` | 17 tablas; UUIDv7 backend (D-70, D-86) |
| Importe / saldo | `BIGINT` | Sufijo `_centavos` (D-71) |
| Movimiento con signo | `BIGINT` | `importe_centavos` ≠ 0 |
| Tasa | `NUMERIC(9,6)` | Factor decimal; sufijo `_tasa` |
| Versión optimista | `INTEGER` o `BIGINT` | Columna `version` |
| Código técnico / estado cerrado | `VARCHAR(64)` | + CHECK (D-74) |
| Texto sin límite funcional | `TEXT` | D-72 |
| Texto con límite semántico | `VARCHAR(n)` | Solo si el límite importa |
| Fecha operativa / cuota / festivo | `DATE` | D-73 |
| Instante | `TIMESTAMPTZ` | UTC almacenado |
| Hora configurable | `TIME WITHOUT TIME ZONE` | D-73 |
| Zona IANA / snapshot | `VARCHAR` | p. ej. `zona_horaria_snapshot` |
| Booleano binario independiente | `BOOLEAN` | D-74 |
| Latitud | `NUMERIC(9,6)` | D-80 |
| Longitud | `NUMERIC(10,6)` | D-80 |
| URI / clave de objeto privado | `TEXT` | Metadatos, no BYTEA de archivo |
| HMAC / hash / huella | `BYTEA` | 32 bytes SHA-256/HMAC-SHA-256 (D-90) |
| Últimos cuatro | `VARCHAR(4)` / `CHAR(4)` | Enmascaramiento |
| Valor cifrado | `BYTEA` o `TEXT` cifrado | Backend; no llaves en BD |
| Snapshot auditoría / idempotencia / metadatos / config | `JSONB` | Solo D-79 |
| Legacy selectivo | `TEXT` / `VARCHAR` | D-87 |

Prohibido: `FLOAT`, `DOUBLE PRECISION`, `REAL` para dinero o tasas.

---

## 2. Dinero y tasas

- Todos los importes monetarios: centavos enteros en `BIGINT`.
- Sufijo obligatorio de columna: `_centavos`.
- `movimientos_cuenta.importe_centavos`: signo permitido; **cero no
  permitido** (positivo aumenta, negativo disminuye).
- Saldos proyectados: reconciliables contra la suma de movimientos.
- Comisión fuera del saldo del crédito (reglas lógicas vigentes).
- Tasas: `NUMERIC(9,6)` como factor (ejemplo: 21.5 % → `0.215000`).
- Sufijo de columna de tasa: `_tasa`.

---

## 3. Política de redondeo (D-88)

1. Cálculos intermedios con precisión decimal exacta (sin flotante).
2. Redondeo final a centavos con modalidad **HALF_UP**.
3. Resultado persistido como `BIGINT` en centavos.
4. Interés y comisión: calcular y redondear **una sola vez**.
5. Cuotas en centavos enteros; la **última cuota** absorbe la diferencia
   para que la suma sea exactamente `total_pagar_centavos`.
6. Misma política en reestructuraciones.

---

## 4. Fechas y zonas (D-73)

| Uso | Tipo | Política |
|---|---|---|
| `created_at`, `updated_at` | `TIMESTAMPTZ` | Instante absoluto |
| Auditoría / confirmaciones / cierres | `TIMESTAMPTZ` | Eventos de dominio |
| `fecha_operativa` | `DATE` | Día de negocio |
| Fechas programadas de cuotas | `DATE` | Sin hora |
| Días festivos | `DATE` | |
| Inicio/fin asignaciones (calendario) | `DATE` o `TIMESTAMPTZ` según diccionario lógico → preferir instante si hay hora | |
| Sesiones (expiración) | `TIMESTAMPTZ` | |
| Procesos automáticos | `TIMESTAMPTZ` | |
| Hora configurable (p. ej. proceso atraso) | `TIME WITHOUT TIME ZONE` | + zona IANA en parámetros |
| Snapshot en jornadas | `zona_horaria_snapshot` | Nombre IANA al abrir/operar |

La zona operativa de negocio se configura por nombre IANA. Los
instantes se guardan en `TIMESTAMPTZ` (práctica: UTC). El día operativo
se deriva en backend con la zona de negocio, no interpretando
`TIMESTAMP` sin zona.

---

## 5. Estados y catálogos (D-74)

| Enfoque | Cuándo |
|---|---|
| Texto + CHECK | Estados técnicos cerrados documentados |
| Tabla de catálogo | Valores administrables (roles, motivos fondo, etc.) |
| BOOLEAN | Solo flags binarios independientes |
| ENUM nativo PostgreSQL | **No en V1** |

Estados derivados (atraso, semáforo) permanecen como proyecciones
según el modelo lógico; no se convierten en máquina de estados
principal del crédito.

---

## 6. JSONB (D-79)

**Permitido:**

- snapshots sanitizados de auditoría;
- respuesta controlada de idempotencia;
- metadatos de evidencia;
- configuración estructurada no financiera.

**Prohibido:**

- saldos e importes;
- relaciones / FK;
- cuotas y movimientos;
- estados principales;
- fechas de conciliación.

---

## 7. GPS (D-80)

V1:

- `latitud NUMERIC(9,6)`;
- `longitud NUMERIC(10,6)`;
- validaciones de rango (CHECK candidata).

Sin PostGIS. Evolución futura: evaluar extensión geográfica y migración
controlada documentada, sin reescribir el dominio de domicilio.

---

## 8. Concurrencia (D-81)

- Nivel: `READ COMMITTED`.
- Transacciones explícitas para escrituras financieras.
- Bloqueo de fila en recursos críticos.
- Columna `version` en cuentas (y donde el diccionario lo exija).
- Idempotencia antes de efectos (D-55, D-68, D-86).
- Restricciones únicas como última defensa.

Orden de bloqueo:

1. idempotencia;
2. crédito;
3. cuentas operativas ordenadas por `id`;
4. validación;
5. dominio;
6. operación financiera;
7. movimientos;
8. proyecciones y versiones;
9. auditoría;
10. commit.

Prohibido: `SKIP LOCKED` en operaciones financieras.

---

## 9. Triggers (D-82)

No crear triggers de lógica financiera en V1.

Cualquier trigger futuro requiere decisión documental y pruebas
automatizadas.

---

## 10. Particionamiento (D-83)

No particionar inicialmente.

Candidatas futuras (por métricas): `movimientos_cuenta`,
`operaciones_financieras`, `eventos_auditoria`, `pagos`,
`aplicaciones_pago_cuota`, `tickets`, `ejecuciones_proceso_atraso`.

---

## 11. Extensiones

No instalar extensiones PostgreSQL en esta fase (incluye PostGIS y
generadores UUID). UUIDv7 se genera en backend (D-86).

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
- `docs/04-database/identificadores-y-exposicion.md`
- `docs/04-database/restricciones-fisicas-postgresql.md`
