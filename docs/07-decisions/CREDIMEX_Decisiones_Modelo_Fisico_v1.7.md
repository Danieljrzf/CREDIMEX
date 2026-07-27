# CREDIMEX — Decisiones de Modelo Físico

**Versión:** 1.7
**Fecha:** 26 de julio de 2026
**Estado:** Aprobado
**Alcance:** Modelo físico preliminar PostgreSQL. Sin SQL ejecutable.
Sin migraciones. Sin extensiones.
**Relación:** Complementa D-01 a D-68. No las reemplaza ni modifica.

---

## Propósito

Documentar las decisiones D-69 a D-91 que fijan identificadores, tipos,
dinero, fechas, catálogos, restricciones físicas candidatas, borrado y
retención, sensibilidad, JSONB, GPS, concurrencia, triggers,
particionamiento, convenciones y orden futuro de migraciones.

Entregables asociados:

- `docs/04-database/modelo-fisico-postgresql.md`
- `docs/04-database/identificadores-y-exposicion.md`
- `docs/04-database/restricciones-fisicas-postgresql.md`
- `docs/04-database/borrado-inactivacion-y-retencion.md`
- `docs/04-database/sensibilidad-cifrado-y-logs.md`
- `docs/04-database/convenciones-fisicas-y-orden-migraciones.md`
- `docs/04-database/riesgos-modelo-fisico.md`

---

## D-69 — Identificadores internos

**Decisión:** Todas las tablas usan `id BIGINT` generado como identidad
del servidor. Modalidad candidata: `GENERATED ALWAYS AS IDENTITY`.

**Motivo:** Joins e índices compactos sobre 67 tablas; PK estable interna.

**Alcance:** Las 67 tablas del inventario lógico.

**Columnas afectadas:** `id` (PK).

**Mecanismo físico:** Identidad PostgreSQL; FK como `BIGINT`.

**Restricciones:** No usar UUID como PK.

**Fuera de alcance:** Generación en Android/web; valores manuales de PK.

Los identificadores heredados, cuando existan, van en campos separados
según D-87 (no en todas las tablas).

---

## D-70 — Identificadores públicos

**Decisión:** Usar `id_publico UUID`, único e inmutable, solo en 17
tablas (lista en D-86 / `identificadores-y-exposicion.md`).

**Motivo:** Exposición API sin enumerar BIGINT; folios de negocio
permanecen separados.

**Alcance:** Las 17 tablas aprobadas; el resto solo `id` interno.

**Mecanismo físico:** Columna `UUID` NOT NULL UNIQUE; generación en
backend (D-86).

**Restricciones:** Inmutable tras creación; no sustituye `folio`.

**Fuera de alcance:** Extensiones PostgreSQL; UUIDv7 en cliente.

---

## D-71 — Dinero y tasas

**Decisión:**

- Importes: `BIGINT`, sufijo `_centavos`, sin float/double.
- `movimientos_cuenta.importe_centavos` admite signo y **no cero**.
- Tasas: `NUMERIC(9,6)` como **factor decimal** (21.5 % = `0.215000`),
  sufijo `_tasa`.

**Motivo:** Exactitud monetaria y reconciliación.

**Alcance:** Todas las columnas monetarias y de tasa del modelo.

**Restricciones:** Prohibido float/double; redondeo según D-88.

**Fuera de alcance:** Contabilidad fiscal completa.

---

## D-72 — Textos

**Decisión:** `TEXT` para contenido sin límite funcional.
`VARCHAR(n)` solo cuando el límite tenga significado real.
Códigos técnicos: `VARCHAR(64)`.

**Motivo:** Evitar límites artificiales; códigos acotados y indexables.

**Alcance:** Columnas de texto del modelo físico.

**Fuera de alcance:** Full-text search avanzado.

---

## D-73 — Fechas y zona

**Decisión:**

- Instantes: `TIMESTAMPTZ`.
- Fechas operativas y programadas: `DATE`.
- Horas configurables: `TIME WITHOUT TIME ZONE`.
- Zona operativa: nombre IANA.
- `zona_horaria_snapshot` en jornadas.

**Motivo:** Evitar ambigüedad DST/expansión geográfica; día operativo
estable.

**Alcance:** Timestamps, `fecha_operativa`, cuotas, festivos, jornadas,
sesiones, procesos.

**Fuera de alcance:** Multi-zona operativa simultánea en V1 (una zona de
negocio configurada).

---

## D-74 — Estados y catálogos

**Decisión:** No ENUM nativo PostgreSQL en V1. Texto + CHECK para
estados técnicos cerrados. Tablas para catálogos administrables.
Booleanos solo para condiciones binarias independientes.

**Motivo:** Evolución por migración sin recrear tipos ENUM.

**Alcance:** Estados de dominio y catálogos documentados.

**Fuera de alcance:** Convertir automáticamente todos los estados en
tablas.

---

## D-75 — Restricciones parciales

**Decisión:** Índices únicos parciales para:

- un calendario `VIGENTE` por crédito;
- un desembolso `CONFIRMADO` por crédito;
- una caja central `ACTIVA`;
- una cuenta operativa activa por propietario;
- asignaciones vigentes cuando corresponda.

CHECK para reglas de una sola fila. Reglas cruzadas en aplicación,
transacción y reconciliación.

**Motivo:** Materializar invariantes frecuentes sin triggers.

**Fuera de alcance:** Triggers financieros (D-82).

---

## D-76 — Claves foráneas

**Decisión:** Política general `NO ACTION` / `RESTRICT`. Sin CASCADE
sobre hechos financieros. CASCADE solo evaluable inicialmente en
`rol_permisos`. `SET NULL` solo en relaciones opcionales no financieras.

**Complemento D-91:** Predeterminado `NO ACTION` no diferible; `RESTRICT`
solo donde se documente rechazo inmediato; sin FK diferibles en V1 salvo
excepción documentada.

**Motivo:** Conservación histórica; evitar borrados en cascada accidentales.

---

## D-77 — Borrado

**Decisión:** No agregar `deleted_at` a todas las tablas. Hechos
financieros e históricos no se eliminan por funciones ordinarias.
Maestros se inactivan. Asignaciones, calendarios, sesiones y reservas se
finalizan, reemplazan o revocan. Borrado físico solo técnico con
retención aprobada.

**Complemento D-91:** Categoría renombrada a «Sin eliminación física
ordinaria»; documentos/evidencias sujetos a política legal futura.

---

## D-78 — Datos sensibles

**Decisión:**

- Teléfonos / cuenta / CLABE: valor cifrado + HMAC búsqueda + últimos
  cuatro.
- Tokens: hash no reversible.
- INE, comprobantes, evidencias: almacenamiento privado externo; BD solo
  metadatos y clave de objeto.
- Nombre, dirección, GPS: operables con acceso restringido y exclusión
  de logs.
- Auditoría sanitizada.

**Complemento D-90:** tipos `BYTEA`, versiones de clave.

**Fuera de alcance:** Diseño de KMS.

---

## D-79 — JSONB

**Decisión:** JSONB solo en:

- snapshots sanitizados de auditoría;
- respuesta controlada de idempotencia;
- metadatos de evidencia;
- configuración estructurada no financiera.

**Prohibido:** saldos, importes, relaciones, cuotas, movimientos,
estados principales, fechas de conciliación.

---

## D-80 — GPS

**Decisión:** V1 con `latitud NUMERIC(9,6)`, `longitud NUMERIC(10,6)` y
validaciones de rango. Sin PostGIS inicialmente. Documentar evolución
geoespacial futura.

---

## D-81 — Concurrencia

**Decisión:** `READ COMMITTED`; transacciones explícitas; bloqueos de
fila; versión optimista; idempotencia; restricciones únicas.

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

No usar `SKIP LOCKED` en operaciones financieras.

---

## D-82 — Triggers

**Decisión:** No crear triggers de lógica financiera en V1. Cualquier
trigger futuro requiere decisión documental y pruebas.

---

## D-83 — Particionamiento

**Decisión:** No particionar inicialmente. Candidatas futuras:

- `movimientos_cuenta`;
- `operaciones_financieras`;
- `eventos_auditoria`;
- `pagos`;
- `aplicaciones_pago_cuota`;
- `tickets`;
- `ejecuciones_proceso_atraso`.

Decisión futura por métricas reales.

---

## D-84 — Convenciones

**Decisión:** Tablas y columnas de dominio en español snake_case.

Usar: `id`, `id_publico`, `<entidad>_id`, `_centavos`, `_tasa`, `estado`,
`version`, `created_at`, `updated_at`.

Eventos: `confirmado_en`, `cerrado_en`, `revocado_en`, `finalizado_en`,
`reemplazado_en`.

Prefijos: `idx_`, `uq_`, `chk_`, `fk_`.

---

## D-85 — Orden de migraciones

**Decisión:** Documentar 15 grupos (detalle en
`convenciones-fisicas-y-orden-migraciones.md`):

1. seguridad;
2. parámetros y catálogos;
3. clientes;
4. rutas;
5. planes y solicitudes;
6. créditos y calendarios;
7. cajas y cuentas;
8. idempotencia y libro;
9. desembolsos;
10. pagos;
11. jornadas y tesorería;
12. cortes e incidencias;
13. restricciones y recuperaciones;
14. auditoría y procesos;
15. restricciones avanzadas.

**Fuera de alcance:** Crear migraciones en esta fase.

---

## D-86 — UUID público generado en backend

**Decisión:** `id_publico` es UUIDv7 generado por la aplicación del
**servidor**. Android y la web futura no generan el identificador
público definitivo. PostgreSQL lo almacena como `UUID`, obligatorio,
único e inmutable en las 17 tablas. Sin extensiones PostgreSQL. Los
reintentos se resuelven mediante idempotencia.

**Tablas afectadas:** las 17 listadas en
`identificadores-y-exposicion.md`.

**Fuera de alcance:** Generación en cliente; extensión `pgcrypto` /
`uuid-ossp` como requisito.

---

## D-87 — Campos heredados selectivos

**Decisión:** No agregar `referencia_legacy`, `sistema_origen` e
`id_externo_origen` a todas las tablas. Solo cuando exista un proceso
documentado de importación. La combinación `sistema_origen` +
`id_externo_origen` es única cuando ambos están presentes. No sustituyen
PK ni `id_publico`.

**Motivo:** Evitar columnas vacías masivas.

**Fuera de alcance:** Definir aquí el mapa de importación completo.

---

## D-88 — Redondeo monetario

**Decisión:**

- cálculos intermedios con precisión decimal exacta;
- redondeo final a centavos;
- modalidad `HALF_UP`;
- resultado almacenado como `BIGINT` en centavos.

El interés y la comisión se calculan y redondean **una sola vez**.
Las cuotas se distribuyen en centavos enteros. La última cuota absorbe
la diferencia para que la suma sea exactamente `total_pagar_centavos`.
Sin punto flotante. Misma política en reestructuraciones.

**Alcance:** Autorización, desembolso, calendario, reestructura (D-44).

---

## D-89 — Cuenta única por lote de carga inicial

**Decisión:** `operaciones_financieras.lote_carga_inicial_id` opcional;
obligatorio para `CARGA_INICIAL_SALDO`. Cada carga afecta exactamente
una cuenta vía su movimiento. **Sin** tabla adicional. **Sin** duplicar
`cuenta_operativa_id` en `operaciones_financieras`.

Unicidad lote + cuenta mediante:

- bloqueo de la fila del lote;
- validación transaccional;
- consulta de operaciones y movimientos existentes;
- reconciliación.

**Clasificación:** APP + TX + REC. **No** UNIQUE directo.

---

## D-90 — Hashes y HMAC

**Decisión:** Usar `BYTEA` para HMAC de teléfono, cuenta y CLABE; hash
de token; huella de idempotencia; hash de integridad de archivos.
Representación esperada SHA-256 / HMAC-SHA-256: **32 bytes**.

Documentar `version_clave_cifrado` y `version_clave_busqueda`.
Cifrado y HMAC en backend. No almacenar llaves o secretos en PostgreSQL.

**Fuera de alcance:** Implementación KMS.

---

## D-91 — Retención y claves foráneas

**Decisión:** Renombrar «Nunca eliminar» a **Sin eliminación física
ordinaria**. Hechos financieros no se borran por funciones normales.
Documentos y evidencias podrán sujetarse a purga, anonimización o
bloqueo conforme a política legal **aprobada**. Conservar metadata
histórica sanitizada cuando corresponda. Sesiones e idempotencias
admiten purga técnica bajo retención aprobada.

FK predeterminada: `NO ACTION`, no diferible. `RESTRICT` solo donde se
documente rechazo inmediato. Sin FK diferibles en V1 salvo excepción
documentada.

**Restricción:** No afirmar retención permanente de PII sin revisión
legal.

---

## Relación con D-01 a D-68

Estas decisiones **no alteran** D-01 a D-68 ni el inventario de 67
tablas. Materializan el modelo lógico v1.6 hacia PostgreSQL.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`
- `docs/04-database/inventario-tablas-logicas.md`
- `docs/04-database/claves-relaciones-restricciones.md`
- `docs/04-database/catalogo-operaciones-financieras.md`
