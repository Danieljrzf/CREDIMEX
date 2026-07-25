# CREDIMEX — Riesgos del modelo lógico

**Estado:** Aprobado (Fase 3A.2)
**Fecha:** 25 de julio de 2026
**Alcance:** Riesgos reales de implementación del modelo lógico.
**Nota:** D-54 a D-68 están **cerradas**; no se listan como pendientes.

Complementa (no reemplaza) `riesgos-y-decisiones-abiertas.md`, cuya deuda
restante es la desalineación de redacción del maestro v1.2.

---

## 1. Proyecciones desincronizadas

| Riesgo | Detalle | Mitigación ya acordada |
|---|---|---|
| Saldo de `cuentas_operativas` ≠ suma de movimientos | Bug de TX o update directo | Misma TX movimiento + saldo + versión; reconciliación periódica |
| Atraso / semáforo de `creditos` desactualizado | Fallo del proceso D-37 o pago sin recálculo | Bitácora `ejecuciones_proceso_atraso`; recálculo tras pagos/reversos/reestructuras |
| `importe_resuelto` / `importe_pendiente` de incidencias | Resoluciones sin actualizar proyección | D-65; reconciliar suma de resoluciones |

---

## 2. Tablas append-only de gran volumen

| Tabla | Riesgo | Mitigación conceptual |
|---|---|---|
| `movimientos_cuenta` | Crecimiento lineal con toda operación | Índices por cuenta/fecha; partición/retención en fase física |
| `eventos_auditoria` | Alto volumen de cambios | Índices por entidad/usuario; política de retención futura |
| `aplicaciones_pago_cuota` | Muchas filas por pago multi-cuota | Índices por pago y cuota |
| `idempotencias_operacion` | Acumulación de claves | Barrido de `EN_PROCESO` stuck; retención de `FALLIDA` |

---

## 3. Auditoría con referencia lógica (D-60)

| Riesgo | Detalle | Mitigación |
|---|---|---|
| `entidad_tipo` + `entidad_id` huérfanos | Borrado lógico o tipado incorrecto | App valida existencia; sin FK polimórfica |
| Tipado inconsistente de `entidad_tipo` | Strings libres divergentes | Catálogo controlado en aplicación |
| Pérdida de vínculo financiero | Evento sin `operacion_financiera_id` cuando sí aplica | Convención: llenar FK real cuando exista OF |

---

## 4. Conflictos de asignaciones temporales (D-57 / D-67)

| Riesgo | Detalle | Mitigación |
|---|---|---|
| Un crédito operable por dos cobradores | Titular de ruta + temporal, o dos temporales | Validación APP de coberturas vigentes |
| Cabecera con detalle de tipo incorrecto | `tipo_alcance` ≠ tabla usada | TX + validación; una sola tabla de detalle |
| Cabecera vacía persistida | Fallo parcial de TX | Cabecera + detalles en la misma TX; ≥1 detalle |

---

## 5. Duplicidad por idempotencia (D-55 / D-68)

| Riesgo | Detalle | Mitigación |
|---|---|---|
| Doble efecto financiero | Reintento sin respetar UK ámbito+clave | UK BD; estados EN_PROCESO/COMPLETADA/FALLIDA |
| Misma clave, huella distinta | Cliente reutiliza clave con otro payload | Error controlado (D-68) |
| Solicitud `EN_PROCESO` abandonada | Crash antes de OF | Timeout/barrido operativo; no crear segunda OF |
| Ámbito incorrecto (URL vs comando) | Confusión de diseño | Ámbito = comando funcional (catálogo D-68) |

---

## 6. Inconsistencias detalle de dominio ↔ MovimientoCuenta

| Riesgo | Detalle | Mitigación |
|---|---|---|
| OF de tesorería sin `operaciones_tesoreria` | Violación D-64 | Misma TX; validación por tipo |
| `GASTO_RUTA` sin `gastos_ruta` | Violación D-66 | Misma TX |
| Detalle sin movimientos / movimientos sin cambiar saldo | Libro incompleto | Regla: saldos solo vía `MovimientoCuenta` |
| Resolución de incidencia sin OF o viceversa | Rompe D-62 | UK `operacion_financiera_id` en resoluciones |

---

## 7. Múltiples calendarios vigentes o desembolsos confirmados

| Riesgo | Detalle | Mitigación |
|---|---|---|
| Dos calendarios `VIGENTE` | Condición de carrera en reestructura | UK parcial / validación APP + REC |
| Dos desembolsos `CONFIRMADO` | Doble activación de saldo | D-31; UK parcial / APP + REC |
| Comisión sin desembolso confirmado o faltante al confirmar | D-61 | TX de confirmación crea la comisión |

---

## 8. Saldos modificados sin movimiento

| Riesgo | Detalle | Mitigación |
|---|---|---|
| Update directo a `saldo_actual_centavos` | Bypass del libro | Disciplina de servicios; reconciliación; versión optimista |
| Ajuste de efectivo por rol no autorizado | Supervisor ejecuta ajuste | D-52: solo administrador |

---

## 9. Exposición de datos personales y bancarios

| Dato | Tablas | Riesgo | Mitigación conceptual |
|---|---|---|---|
| Nombre, teléfono, GPS, documentos | clientes, domicilios, documentos | PII | Acceso por rol; archivos privados |
| Credenciales / tokens | usuarios, sesiones_token | Secreto | Hash; fuera de auditoría parcial D-16 |
| Referencias bancarias | `cuentas_bancarias`, transferencias | Dato financiero sensible | Acceso restringido; no como ContraparteExterna |
| Evidencias de archivo | `evidencias_operacion`, documentos | Contenido sensible | Almacenamiento privado; `CON_DOCUMENTO` |

---

## 10. Índices costosos

| Índice / patrón | Riesgo | Mitigación |
|---|---|---|
| Movimientos por cuenta + fecha | Escritura intensa | Evaluar partición en físico |
| Auditoría por usuario + fecha | Volumen | Retención; índices parciales |
| Semáforo en créditos | Recálculos frecuentes | Índice parcial solo `ACTIVO`; no sobreindexar |
| Búsqueda de duplicados por nombre | Coste y ruido | Complementar con teléfono; no bloquear altas |

Detalle: `indices-conceptuales.md`.

---

## 11. Reglas que no pueden protegerse solo con base de datos

Estas requieren **aplicación**, **transacción** y/o **reconciliación**:

1. Máximo cinco créditos activos con saldo > 0.
2. Un crédito operable por un solo cobrador a la vez (ruta + temporal).
3. FIFO de aplicaciones a cuotas y recálculo de atraso.
4. Cierre de incidencia con pendiente = 0 + confirmación explícita y reglas de rol (D-65).
5. Huella de idempotencia y catálogo de ámbitos (D-68).
6. Coherencia tipo OF ↔ fila de detalle (`operaciones_tesoreria` / `gastos_ruta`).
7. Bloqueo de `CARGA_INICIAL_SALDO` tras el primer cierre de puesta en marcha.
8. Existencia de entidad referenciada en auditoría lógica (D-60).
9. Cuota final ajustada al total exacto; comisión fuera del saldo.
10. Disponible = saldo − reservas activas (D-26).
11. Políticas de festivos sobre cuotas ya exigibles (D-53).
12. Autorización de límites de crédito (D-12) y autoautorización.

Las candidatas BD (PK, FK, UK simples, CHECK de dueño exclusivo) están en
`claves-relaciones-restricciones.md`.

---

## 12. Deuda documental (no es riesgo de modelo lógico nuevo)

El maestro v1.2 aún no refleja textualmente precisiones de v1.3–v1.6.
No se modifica en esta fase. Ver `riesgos-y-decisiones-abiertas.md` §1.

---

## Referencias

- `docs/04-database/claves-relaciones-restricciones.md`
- `docs/04-database/indices-conceptuales.md`
- `docs/04-database/riesgos-y-decisiones-abiertas.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`
