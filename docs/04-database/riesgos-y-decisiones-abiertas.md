# CREDIMEX — Riesgos y decisiones abiertas

**Estado:** Vigente tras Decisiones modelo v1.5  
**Nota:** Las decisiones D-21 a D-32 están en
`docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.4.md`.  
Las decisiones D-33 a D-53 están en
`docs/07-decisions/CREDIMEX_Decisiones_Modelo_Datos_v1.5.md` y **cierran**
las decisiones abiertas A–H del modelo conceptual y los residuales
operativos de carga inicial, ajustes de efectivo y días festivos.

---

## 1. Deuda documental (maestro v1.2)

El documento maestro aún no refleja textualmente las precisiones de v1.3,
v1.4 y v1.5. No son contradicciones de diseño vigentes; son
**desalineaciones de redacción** a corregir cuando se autorice actualizar el
maestro:

- RN-CLI-008 vs conteo `ACTIVO` ∧ saldo > 0;
- RN-COM-003 vs comisión liquidada en el desembolso (movimientos brutos
  D-35);
- RN-PAG-001 vs FIFO a cuotas solo para atraso;
- rutas de carpetas en la Parte III del maestro;
- matriz “auditoría parcial” vs D-16;
- D-09 aún enumera estados derivados (`ATRASADO`, `VENCIDO`,
  `REESTRUCTURADO_CON_SALDO`) que el modelo conceptual trata como proyecciones,
  no como estados principales del crédito.

---

## 2. Decisiones A–H — cerradas en v1.5

| Pendiente | Cierre |
|---|---|
| A. Nombres canónicos de tipos | D-33 + `catalogo-operaciones-financieras.md` (20 códigos) |
| B. Contraparte externa | D-34, D-48 (`ContraparteExterna`) |
| C. Primer pago retenido | D-35, D-36, D-45, D-49 |
| D. Proceso diario de atraso | D-37 (`EjecucionProcesoAtraso`) |
| E. Excepción de cuarta noche | D-38, D-46, UC-27 |
| F. Multi-caja | D-39 |
| G. JornadaCajaCentral | D-40 |
| H. Evidencia de ajuste | D-41 (`EvidenciaOperacion`) |

Complementos cerrados en la misma versión: D-42 / D-51 (`CARGA_INICIAL_SALDO`
y puesta en marcha), D-43 (transiciones de cuota), D-44 (reestructuración),
D-47 (entrega con diferencia), D-50 (motivo del fondo), D-52 (ajustes de
efectivo exclusivos del administrador), D-53 (política de días festivos).

---

## 3. Decisiones residuales abiertas

Ninguna decisión bloqueante del modelo conceptual permanece abierta tras
v1.5 (incluyendo D-51 a D-53).

La única deuda pendiente es la **desalineación de redacción del maestro
v1.2** (§1), que no se modifica en esta normalización.

---

## 4. Riesgos residuales de implementación

| Riesgo | Mitigación conceptual ya acordada |
|---|---|
| Duplicar ruta en cliente | Solo `AsignacionClienteRuta` |
| Cargar `EFECTIVO_COBRADOR` desde origen abstracto | D-25, D-50 |
| Olvidar reserva en desembolso pendiente | `ReservaEfectivo` D-26 |
| Segundo desembolso confirmado | Restricción 1 CONFIRMADO D-31 |
| Editar proyección sin movimiento | Prohibido; reconciliación |
| Usar reverso de pago para faltantes | `cash-differences.md`, D-47 |
| Contar castigados en el máximo de cinco | Solo `ACTIVO` ∧ saldo > 0 |
| Cancelar crédito activo | Prohibido D-23 |
| Olvidar movimiento bruto de comisión | D-35 |
| Quinta noche de efectivo | Prohibido D-46 |
| Usar `CARGA_INICIAL_SALDO` fuera de puesta en marcha | D-42, D-51 |
| Supervisor ejecuta ajuste de efectivo | Prohibido D-52 |
| Regenerar cuotas históricas al cambiar festivo | Prohibido D-53 |

---

## 5. Próximos entregables sugeridos (sin ejecutar aquí)

1. Diagrama entidad-relación conceptual.
2. Diccionario de datos (aún sin migraciones).
3. Arquitectura técnica detallada.
4. Contrato OpenAPI inicial.
5. Actualizar el documento maestro v1.2 cuando se autorice (deuda §1).
