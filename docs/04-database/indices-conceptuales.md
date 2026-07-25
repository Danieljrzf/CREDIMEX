# CREDIMEX — Índices conceptuales

**Estado:** Aprobado (Fase 3A.2)
**Fecha:** 25 de julio de 2026
**Alcance:** Índices lógicos. Sin sintaxis SQL.
**Fuentes:** Inventario, diccionarios, consultas frecuentes de casos de uso.

Cada índice indica: tabla, columnas, propósito, consulta que acelera,
condición parcial conceptual (si aplica) y riesgo de costo/mantenimiento.

---

## 1. Clientes

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Clientes por nombre | `clientes` | `nombre` | Búsqueda operativa | Listar/buscar por nombre | No | Medio: nombres frecuentes; posible índice de texto según motor |
| Clientes por teléfono | `clientes` | `telefono_principal` | Contacto y anti-duplicado | Buscar por teléfono | No | Bajo |
| Clientes por ruta vigente | `asignaciones_cliente_ruta` | `ruta_id`, `cliente_id` | Cartera de ruta | Clientes de una ruta | Solo `estado = VIGENTE` | Bajo–medio |
| Duplicados potenciales nombre+teléfono | `clientes` | `nombre`, `telefono_principal` | Detección RN-CLI-006 | Candidatos duplicados | No | Medio: falsos positivos; no sustituye ConfirmacionNoDuplicado |
| Contactos por teléfono | `contactos_alternativos` | `telefono` | Coincidencias secundarias | Anti-duplicado ampliado | Solo vigentes | Bajo |

---

## 2. Créditos

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Créditos activos por cliente | `creditos` | `cliente_id`, `estado`, `saldo_actual_centavos` | Máximo de cinco | Contar/listar activos con saldo > 0 | `estado = ACTIVO` | Bajo |
| Créditos por cliente | `creditos` | `cliente_id` | Expediente | Historial de créditos | No | Bajo |
| Créditos por semáforo | `creditos` | `semaforo_actual`, `estado` | Priorización cobranza | Tablero semáforo | `estado = ACTIVO` | Medio: proyección recalculada con frecuencia |
| Créditos por ruta (vía cliente) | `asignaciones_cliente_ruta` + `creditos` | ruta vigente + `cliente_id` | Cartera cobrador titular | Créditos de ruta | Asignación `VIGENTE` | Medio: consulta compuesta app |
| Créditos por cobrador temporal | `asignaciones_temporales_creditos` | `credito_id`, `asignacion_temporal_id` | Cobertura temporal | ¿Quién opera el crédito? | Cabecera `VIGENTE` | Medio: validar no doble cobertura |
| Asignación temporal por cobrador | `asignaciones_temporales_cobranza` | `cobrador_id`, `estado`, `vigente_desde` | Agenda temporal | Asignaciones del cobrador | `estado = VIGENTE` | Bajo |
| Titular ruta–cobrador | `asignaciones_ruta_cobrador` | `ruta_id`, `estado` | Titular vigente | Cobrador de la ruta | `estado = VIGENTE` | Bajo |

---

## 3. Calendarios y cuotas

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Calendario vigente por crédito | `calendarios` | `credito_id`, `estado` | Un vigente | Obtener calendario actual | `estado = VIGENTE` | Bajo; crítico para integridad |
| Cuotas pendientes por fecha | `cuotas_programadas` | `fecha_programada`, `estado` | Cobranza del día | Cuotas PENDIENTE/PARCIAL del día | Estados exigibles | Medio: tabla grande |
| Cuotas por calendario y número | `cuotas_programadas` | `calendario_id`, `numero` | Orden FIFO | Aplicación y listado | No | Bajo |

---

## 4. Pagos y tickets

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Pagos por crédito y fecha | `pagos` | `credito_id`, `fecha_operativa` | Historial | Pagos del crédito | No | Bajo–medio |
| Pagos por cobrador y fecha | `pagos` | `cobrador_id`, `fecha_operativa` | Jornada | Pagos del día | Medio efectivo | Bajo |
| Aplicaciones por cuota | `aplicaciones_pago_cuota` | `cuota_programada_id` | Cobertura | Importe cubierto | No | Medio: volumen |
| Aplicaciones por pago | `aplicaciones_pago_cuota` | `pago_id` | Detalle ticket/reverso | Deshacer cobertura | No | Bajo |
| Tickets por folio | `tickets` | `folio` | Reimpresión / consulta | Buscar ticket | No | Bajo |
| Transferencias por cuenta y fecha | `transferencias_bancarias` | `cuenta_bancaria_id`, fechas vía OF | Conciliación banco | Transferencias del día | No | Bajo |

---

## 5. Operaciones, idempotencia y movimientos

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Operaciones por folio | `operaciones_financieras` | `folio` | Localización única | Buscar folio | No (UK) | Bajo |
| Operaciones por tipo y fecha | `operaciones_financieras` | `tipo`, `fecha_operativa` | Reportes | Operaciones del día/tipo | No | Medio |
| Operaciones por jornada cobrador | `operaciones_financieras` | `jornada_cobrador_id` | Corte cobrador | Ops de la jornada | No nulos | Bajo |
| Operaciones por jornada caja | `operaciones_financieras` | `jornada_caja_central_id` | Corte caja | Ops de la jornada | No nulos | Bajo |
| Operaciones hijas por padre | `operaciones_financieras` | `operacion_padre_id` | Retenido/reverso | Cadena de OF | No nulos | Bajo |
| Operaciones por lote carga | `operaciones_financieras` | `lote_carga_inicial_id` | Puesta en marcha | OF del lote | No nulos | Bajo |
| Idempotencia ámbito+clave | `idempotencias_operacion` | `ambito`, `clave` | Anti-duplicado | Reintento de comando | No (UK) | Bajo; crítico |
| Idempotencia en proceso | `idempotencias_operacion` | `estado`, `creado_en` | Barrido stuck | Limpiar EN_PROCESO | `EN_PROCESO` | Bajo |
| Movimientos por cuenta y fecha | `movimientos_cuenta` | `cuenta_operativa_id`, `registrado_en` | Extracto / reconcile | Movimientos de cuenta | No | **Alto volumen** |
| Movimientos por operación | `movimientos_cuenta` | `operacion_financiera_id` | Detalle folio | Asientos del folio | No | Bajo |

---

## 6. Jornadas, entregas, cortes e incidencias

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Jornadas cobrador abiertas | `jornadas_cobrador` | `estado`, `fecha_operativa` | Operación diaria | Jornadas ABIERTA/EN_CORTE | Estados abiertos | Bajo |
| Jornada por cobrador+fecha | `jornadas_cobrador` | `cobrador_id`, `fecha_operativa` | UK / lookup | Obtener jornada | No (UK) | Bajo |
| Jornadas caja abiertas | `jornadas_caja_central` | `estado`, `fecha_operativa` | Tesorería | Jornadas abiertas | Estados abiertos | Bajo |
| Jornada caja por caja+fecha | `jornadas_caja_central` | `caja_central_id`, `fecha_operativa` | UK | Obtener jornada | No (UK) | Bajo |
| Entregas pendientes | `entregas_efectivo` | `estado`, fechas | Doble confirmación | Entregas no terminales | Estados no confirmados/cancelados | Bajo–medio |
| Entregas por cobrador | `entregas_efectivo` | `cobrador_id`, `estado` | Seguimiento | Entregas del cobrador | No | Bajo |
| Cortes cobrador por fecha | `cortes_cobrador` vía jornada | `fecha_operativa` (jornada) | Histórico cortes | Cortes del día | No | Bajo |
| Cortes caja por fecha | `cortes_caja_central` vía jornada | `fecha_operativa` | Histórico | Cortes del día | No | Bajo |
| Incidencias abiertas | `incidencias_caja` | `estado`, `tipo` | Seguimiento faltantes | ABIERTA / EN_REVISION | No resueltas | Bajo |
| Resoluciones por incidencia | `resoluciones_incidencia_caja` | `incidencia_caja_id` | Importe resuelto | Sumar resoluciones | No | Bajo |
| Reservas activas por cobrador | `reservas_efectivo` | `cobrador_id`, `estado` | Disponible | Sumar ACTIVAS | `ACTIVA` | Bajo |
| Desembolsos por crédito y estado | `desembolsos` | `credito_id`, `estado` | ≤1 CONFIRMADO | Validar confirmado | `CONFIRMADO` parcial | Bajo |

---

## 7. Auditoría y configuración

| Índice conceptual | Tabla | Columnas | Propósito | Consulta | Parcial | Riesgo |
|---|---|---|---|---|---|---|
| Auditoría por entidad | `eventos_auditoria` | `entidad_tipo`, `entidad_id` | Historial entidad | Eventos de un registro | No | **Alto volumen** |
| Auditoría por operación | `eventos_auditoria` | `operacion_financiera_id` | Trazabilidad OF | Eventos ligados a folio | No nulos | Medio |
| Auditoría por usuario y fecha | `eventos_auditoria` | `usuario_id`, `registrado_en` | Supervisión | Actividad del usuario | No | Alto volumen |
| Evidencias por operación | `evidencias_operacion` | `operacion_financiera_id` | Sustento | Adjuntos del folio | No | Bajo |
| Parámetros vigentes por clave | `parametros_sistema` | `clave`, `vigente_desde` | Config | Parámetro actual | Vigentes | Bajo |
| Festivos por fecha | `dias_festivos` | `fecha` | Calendarios | ¿Es festivo? | Activos | Bajo |
| Ejecuciones proceso atraso | `ejecuciones_proceso_atraso` | `iniciado_en`, `disparador` | Monitoreo batch | Últimas corridas | No | Bajo |
| Lotes carga por estado | `lotes_carga_inicial` | `estado` | Puesta en marcha | Lotes abiertos | No cerrados | Bajo |

---

## 8. Notas de diseño

1. Los índices parciales conceptuales (solo `VIGENTE`, solo `ACTIVO`, etc.)
   se materializarán según capacidades de PostgreSQL en la fase física;
   aquí solo se declara la intención.
2. `movimientos_cuenta` y `eventos_auditoria` son las tablas de mayor
   crecimiento esperado; sus índices por fecha/cuenta/usuario deben
   evaluarse con retención y partición en el modelo físico.
3. No se proponen índices sobre columnas de alta cardinalidad sin consulta
   frecuente documentada.
4. Las UK ya listadas en `claves-relaciones-restricciones.md` implican
   índice único y no se duplican aquí salvo por claridad operativa
   (folio, idempotencia, jornadas).

---

## Referencias

- `docs/04-database/claves-relaciones-restricciones.md`
- `docs/04-database/inventario-tablas-logicas.md`
