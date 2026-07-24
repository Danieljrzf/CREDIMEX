# CREDIMEX — Riesgos y decisiones abiertas

**Estado:** Vigente tras Decisiones modelo v1.4  
**Nota:** Las decisiones D-21 a D-32 **no** se listan aquí como pendientes;
están documentadas en
`docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`.

---

## 1. Deuda documental (maestro v1.2)

El documento maestro aún no refleja textualmente las precisiones de v1.3 y
v1.4. No son contradicciones de diseño vigentes; son **desalineaciones de
redacción** a corregir cuando se autorice actualizar el maestro:

- RN-CLI-008 vs conteo `ACTIVO` ∧ saldo > 0;
- RN-COM-003 vs comisión liquidada en el desembolso;
- RN-PAG-001 vs FIFO a cuotas solo para atraso;
- rutas de carpetas en la Parte III del maestro;
- matriz “auditoría parcial” vs D-16;
- `collector-workday.md` aún menciona `PENDIENTE` persistible; D-22 lo vuelve
  conceptual (actualizar jornada cuando se autorice).

---

## 2. Decisiones realmente abiertas

### A. Nombres canónicos de tipos de OperacionFinanciera

Existe el tipo `AJUSTE_ADMINISTRATIVO_DESEMBOLSO`. Falta congelar el catálogo
completo de códigos de operación (pago, reverso, fondo, etc.) antes del
diccionario físico.

### B. Contraparte conceptual de entradas externas

D-25 exige custodia real (`CAJA_CENTRAL` / `CUENTA_BANCARIA`). Falta detallar
si la “contraparte” externa se modela solo como metadatos (origen, documento,
motivo) o como entidad `ContraparteExterna` en una fase posterior.

### C. Primer pago retenido: misma operación o hija

En modalidad 3 de desembolso, si el `Pago` del primer retenido comparte exactamente
el mismo folio de `OperacionFinanciera` del desembolso o es operación hija
ligada. Ambas cumplen D-17; hay que fijarlo en el diccionario.

### D. Proceso diario de atraso

D-28 exige recálculo diario. Falta definir ventana horaria y si corre como
trabajo programado del servidor (sin diseñar endpoints aún).

### E. Excepción de cuarta noche (D-15)

Está decidido el bloqueo con excepción administrativa auditada. Falta el
detalle de entidad/flujo de la excepción (quién aprueba, duración, efecto en
disponible).

### F. Multi-caja futura

D-29 deja V1 con una caja activa y campos `codigo`/`nombre`/`estado`, sin
`sucursal_id`. La estrategia de migración a varias cajas queda para una fase
posterior.

### G. Alcance exacto de JornadaCajaCentral

UC-25 define corte de caja central. Falta alinear un documento de estados de
jornada de caja central equivalente al del cobrador (hoy solo esbozado en el
catálogo de estados).

### H. Evidencia del ajuste administrativo

D-30 exige evidencia u observación. Falta precisar si la evidencia es solo
texto, archivo privado, o ambos, antes del diccionario de documentos.

---

## 3. Riesgos residuales de implementación

| Riesgo | Mitigación conceptual ya acordada |
|---|---|
| Duplicar ruta en cliente | Solo `AsignacionClienteRuta` |
| Cargar `EFECTIVO_COBRADOR` desde origen abstracto | D-25 |
| Olvidar reserva en desembolso pendiente | `ReservaEfectivo` D-26 |
| Segundo desembolso confirmado | Restricción 1 CONFIRMADO D-31 |
| Editar proyección sin movimiento | Prohibido; reconciliación |
| Usar reverso de pago para faltantes | `cash-differences.md` |
| Contar castigados en el máximo de cinco | Solo `ACTIVO` ∧ saldo > 0 |
| Cancelar crédito activo | Prohibido D-23 |

---

## 4. Próximos entregables sugeridos (sin ejecutar aquí)

1. Actualizar `collector-workday.md` a D-22 (cuando se autorice).
2. Diagrama entidad-relación conceptual.
3. Diccionario de datos (aún sin migraciones).
4. Cerrar el catálogo de códigos de `OperacionFinanciera`.
5. ADR formales en `docs/07-decisions/` si se desea granularidad adicional.
