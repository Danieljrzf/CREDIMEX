# CREDIMEX — Identificadores y exposición

**Estado:** Aprobado (Fase 3A.3)
**Decisiones:** D-69, D-70, D-86, D-87.
**Alcance:** Sin SQL. Sin extensiones PostgreSQL.

---

## 1. Tipos de identificador

| Tipo | Descripción | Dónde |
|---|---|---|
| `id` interno | `BIGINT` identidad servidor; PK; inmutable | Las **67** tablas |
| `id_publico` | `UUID` UUIDv7; único; inmutable; expuesto a API | **17** tablas |
| Folio de negocio | Texto de comprobante/operación (`folio`) | Operaciones, tickets, entregas, etc. |
| Identificador heredado | `referencia_legacy` / `sistema_origen` / `id_externo_origen` | Solo tablas con importación documentada (D-87) |
| Clave de idempotencia | `ambito` + `clave` (+ huella) | `idempotencias_operacion` |

Ninguno sustituye a otro: el folio no es la PK; el UUID no es el folio;
los campos legacy no son la PK ni el `id_publico`.

---

## 2. Generación de `id_publico` (D-86)

- Generado por la **aplicación del servidor** (backend).
- Formato: **UUIDv7**.
- Android y la futura web **no** generan el identificador público
  definitivo.
- PostgreSQL solo **almacena** `UUID` obligatorio, único e inmutable.
- Sin extensiones PostgreSQL.
- Reintentos de red: se resuelven con **idempotencia**, no regenerando
  un segundo `id_publico` para el mismo comando exitoso.

---

## 3. Las 17 tablas con `id_publico`

1. `usuarios`
2. `clientes`
3. `rutas`
4. `solicitudes_credito`
5. `creditos`
6. `pagos`
7. `desembolsos`
8. `tickets`
9. `operaciones_financieras`
10. `entregas_efectivo`
11. `incidencias_caja`
12. `jornadas_cobrador`
13. `jornadas_caja_central`
14. `cortes_cobrador`
15. `cortes_caja_central`
16. `documentos_cliente`
17. `evidencias_operacion`

Las demás 50 tablas del inventario **no** llevan `id_publico`.

---

## 4. Identificadores internos (D-69)

- Modalidad candidata: `GENERATED ALWAYS AS IDENTITY`.
- Todas las FK apuntan a `id BIGINT`.
- Auditoría lógica (`entidad_id`) sigue usando el identificador interno
  de la entidad referenciada (D-60).

---

## 5. Campos heredados selectivos (D-87)

No se agregan a todas las tablas.

Cuando exista proceso documentado de importación, pueden incluirse:

- `referencia_legacy`;
- `sistema_origen`;
- `id_externo_origen`.

Unicidad candidata: (`sistema_origen`, `id_externo_origen`) cuando
**ambos** están presentes.

---

## 6. Exposición recomendada

| Identificador | Cliente Android / web | Interno servidor / BD |
|---|---|---|
| `id` BIGINT | No exponer en API pública | Sí |
| `id_publico` | Sí, en las 17 tablas | Sí |
| Folio | Sí, cuando el caso de uso lo requiera | Sí |
| Legacy | No (salvo herramientas de migración) | Sí, si aplica |
| Idempotencia (`clave`) | Enviado por cliente; no es ID de entidad | Sí |

---

## Referencias

- `docs/04-database/inventario-tablas-logicas.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
