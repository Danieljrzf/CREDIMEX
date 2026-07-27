# CREDIMEX — Riesgos del modelo físico

**Estado:** Aprobado (Fase 3A.3)
**Decisiones:** D-69 a D-91.
**Nota:** D-69 a D-91 están **cerradas**; no se listan como pendientes
de decisión. Este documento registra riesgos residuales de
implementación.

Complementa `riesgos-modelo-logico.md` y
`riesgos-y-decisiones-abiertas.md` (deuda de redacción del maestro).

---

## 1. Identificadores

| Riesgo | Mitigación |
|---|---|
| Cliente intenta generar UUID | D-86: solo backend; API rechaza |
| Reloj incorrecto en UUIDv7 | NTP / generación controlada en servidor |
| Exponer BIGINT en API | Contrato API usa `id_publico` en las 17 tablas |
| Columnas legacy masivas | D-87: solo con importación documentada |

---

## 2. Dinero y redondeo

| Riesgo | Mitigación |
|---|---|
| Uso accidental de float | Revisión de código; tipos `BIGINT`/`NUMERIC` |
| Cuotas que no suman el total | Última cuota absorbe (D-88); REC |
| Doble redondeo de interés/comisión | Una sola vez (D-88) |

---

## 3. Restricciones e integridad

| Riesgo | Mitigación |
|---|---|
| UNIQUE parcial desalineado del catálogo de estados | Grupo 15 + pruebas |
| Asumir UNIQUE lote+cuenta en BD | D-89: APP + TX + REC explícito |
| ORM que no expresa CHECK/parciales | Pruebas de integración contra PostgreSQL |
| CASCADE accidental | D-76 / D-91: NO ACTION; sin CASCADE financiero |

---

## 4. Concurrencia

| Riesgo | Mitigación |
|---|---|
| Deadlocks | Orden estricto D-81 |
| Uso de `SKIP LOCKED` financiero | Prohibido D-81 |
| Proyección desfasada | Paso 8 del orden + REC |

---

## 5. Sensibilidad

| Riesgo | Mitigación |
|---|---|
| PII en logs o JSONB de auditoría | D-78 / D-79 sanitización |
| Búsqueda imposible tras cifrado | HMAC + `version_clave_busqueda` |
| Secretos en BD | Prohibido D-90; KMS fuera de alcance aún |
| Purga legal de documentos sin política | D-91: requiere política legal aprobada |

---

## 6. Volumen y evolución

| Riesgo | Mitigación |
|---|---|
| Particionar demasiado pronto | D-83: métricas primero |
| PostGIS prematuro | D-80: numérico V1 |
| Triggers “temporales” de negocio | D-82: prohibidos en V1 |

---

## 7. Deuda documental no física

Desalineación de redacción del maestro v1.2 respecto a v1.3–v1.6:
no bloquea el modelo físico; no se modifica en esta fase.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
- `docs/04-database/riesgos-modelo-logico.md`
