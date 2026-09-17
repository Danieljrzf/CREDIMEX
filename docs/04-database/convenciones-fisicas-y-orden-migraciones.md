# CREDIMEX — Convenciones físicas y orden de migraciones

**Estado:** Aprobado (Fase 3A.3)
**Decisiones:** D-84, D-85.
**Alcance:** Convenciones y grupos futuros. **No** crea migraciones.

---

## 1. Convenciones de nombres (D-84)

| Elemento | Convención |
|---|---|
| Tablas de dominio | español `snake_case` (inventario v1.6) |
| Columnas de dominio | español `snake_case` |
| PK | `id` |
| UUID público | `id_publico` |
| FK | `<entidad>_id` |
| Importes | sufijo `_centavos` |
| Tasas | sufijo `_tasa` |
| Estado | `estado` |
| Versión optimista | `version` |
| Alta / cambio fila | `created_at`, `updated_at` (`TIMESTAMPTZ`) |
| Eventos de dominio | `confirmado_en`, `cerrado_en`, `revocado_en`, `finalizado_en`, `reemplazado_en` |
| Índices | prefijo `idx_` |
| Uniques | prefijo `uq_` |
| Checks | prefijo `chk_` |
| Foreign keys | prefijo `fk_` |

No mezclar idiomas de forma arbitraria: se conserva el español del
modelo lógico aprobado.

Para RBAC, v2.4 fija timestamps `NOT NULL` sin `DEFAULT`: roles y
permisos llevan `created_at` / `updated_at`; `rol_permisos` solo
`created_at`.

---

## 2. Nombres de migraciones

Patrón:

- prefijo secuencial por grupo D-85;
- descripción breve en snake_case.

Las primeras migraciones de dominio (3B.3.1B / v2.5) existen en el
repositorio:

- `2026_09_16_000001_create_roles_table.php`;
- `2026_09_16_000002_create_permisos_table.php`;
- `2026_09_16_000003_create_rol_permisos_table.php`.

Están implementadas en código; no se afirma despliegue persistente.

---

## 3. Orden de migraciones — 15 grupos (D-85)

| # | Grupo | Tablas / contenido | Depende de |
|---|---|---|---|
| 1 | Seguridad | `roles`, `permisos`, `rol_permisos`, `usuarios`, `dispositivos`, `sesiones_token` | — |
| 2 | Parámetros y catálogos | `parametros_sistema`, `dias_festivos`, `motivos_entrega_fondo` | 1 (usuarios si hay auditor) |
| 3 | Clientes | `clientes`, contactos, referencias, domicilios, documentos, confirmaciones | 1 |
| 4 | Rutas | `rutas`, asignaciones ruta/cliente, temporales (+3 detalles) | 1, 3 |
| 5 | Planes y solicitudes | `planes_credito`, `versiones_plan`, `solicitudes_credito`, `autorizaciones_credito` | 3 |
| 6 | Créditos y calendarios | `creditos`, versiones condiciones, calendarios, cuotas, `renovaciones` | 5 |
| 7 | Cajas y cuentas | `cajas_centrales`, `cuentas_bancarias`, `cuentas_operativas`, `contrapartes_externas` | 1, 6 (crédito), 7 parcial |
| 8 | Idempotencia y libro | `lotes_carga_inicial`, `idempotencias_operacion`, `operaciones_financieras`, `movimientos_cuenta` | 7 |
| 9 | Desembolsos | `desembolsos`, `comisiones`, `reservas_efectivo`, `reestructuraciones` | 6, 8 |
| 10 | Pagos | `pagos`, aplicaciones, reversos, transferencias, tickets, reimpresiones | 6, 7, 8 |
| 11 | Jornadas y tesorería | jornadas, entregas, `gastos_ruta`, `operaciones_tesoreria`, excepciones | 1, 7, 8 |
| 12 | Cortes e incidencias | cortes, `incidencias_caja`, `resoluciones_incidencia_caja` | 11, 8 |
| 13 | Restricciones y recuperaciones | restricciones, castigos, recuperaciones, visitas, promesas | 6, 8 |
| 14 | Auditoría y procesos | `eventos_auditoria`, `evidencias_operacion`, `ejecuciones_proceso_atraso` | 8 |
| 15 | Restricciones avanzadas | UNIQUE parciales, CHECK compuestos, índices de consulta | 1–14 |

---

## 4. Dependencias y FK posteriores

- `cuentas_operativas` referencia usuarios, cajas, cuentas bancarias y
  créditos → grupo 7 después de existir esas entidades.
- `operaciones_financieras` referencia jornadas (opcionales) → las FK
  a jornadas pueden añadirse en grupo 11/15 si el libro nace antes
  (FK posteriores / nullable hasta cablear).
- `creditos.solicitud_credito_id` y `solicitudes_credito.credito_id`
  forman ciclo lógico: crear columnas nullable y completar en la misma
  fase de créditos/solicitudes, o diferir una FK al grupo 15 **sin**
  FK diferible de PostgreSQL (D-91): validación APP hasta cablear.
- Detalles de asignación temporal dependen de la cabecera (grupo 4).
- Restricciones parciales (vigente/confirmado/activa) van al **grupo 15**
  para no acoplar el alta inicial de tablas.

No se usan FK diferibles en V1 salvo excepción documentada (D-91).

---

## 5. Qué no se hace en esta fase

- No se escriben archivos de migración.
- No se ejecuta SQL.
- No se inicializa PostgreSQL.

---

## Referencias

- `docs/04-database/inventario-tablas-logicas.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
