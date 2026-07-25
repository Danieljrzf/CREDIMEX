# CREDIMEX — Diccionario: libro, auditoría y procesos

**Estado:** Aprobado (Fase 3A.2)
**Dominios:** 13–15
**Alcance:** Columnas conceptuales. Sin tipos SQL.
**Fuentes:** Inventario lógico, D-54 a D-68, modelo de movimientos, D-33.

Convenciones: ver `diccionario-identidad-clientes-rutas.md`.

---

## 1. `operaciones_financieras`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Tipo | `tipo` | Código catálogo cerrado | código de catálogo | sí | capturado | inmutable | — | D-33 (20 códigos) | financiera |
| Folio | `folio` | Folio único | texto corto | sí | generado | inmutable | — | UK | financiera |
| Estado | `estado` | Estado lógico | código de catálogo | sí | capturado | mutable | CONFIRMADA | — | — |
| Fecha operativa | `fecha_operativa` | Día operativo | fecha | sí | capturado | inmutable | — | — | — |
| Registrado en | `registrado_en` | Timestamp | fecha y hora | sí | generado | inmutable | ahora | — | auditoría |
| Usuario | `usuario_id` | Ejecutor | identificador | sí | capturado | inmutable | — | — | auditoría |
| Motivo | `motivo` | Motivo | texto largo | no | capturado | inmutable | — | — | auditoría |
| Medio | `medio` | Si aplica | código de catálogo | no | capturado | inmutable | — | EFECTIVO/TRANSFERENCIA/RETENIDO | — |
| Operación padre | `operacion_padre_id` | Autorrelación | identificador | no | capturado | inmutable | — | D-36, reversos | financiera |
| Jornada cobrador | `jornada_cobrador_id` | Día cobrador | identificador | no | capturado | inmutable | — | — | — |
| Jornada caja | `jornada_caja_central_id` | Día caja | identificador | no | capturado | inmutable | — | — | — |
| Contraparte | `contraparte_externa_id` | Externa | identificador | no | capturado | inmutable | — | D-34 | — |
| Snapshot nombre contraparte | `contraparte_nombre_snapshot` | Nombre al momento | texto corto | no | capturado | inmutable | — | — | — |
| Snapshot ref. contraparte | `contraparte_referencia_snapshot` | Ref. al momento | texto corto | no | capturado | inmutable | — | — | — |
| Lote carga | `lote_carga_inicial_id` | Si CARGA_INICIAL | identificador | no | capturado | inmutable | — | D-56 obligatorio si tipo | — |
| Fecha hora corte carga | `carga_fecha_hora_corte` | Corte físico | fecha y hora | no | capturado | inmutable | — | D-51 | — |
| Responsable conteo | `carga_responsable_conteo_id` | Usuario | identificador | no | capturado | inmutable | — | D-51 | auditoría |

## 2. `cuentas_operativas`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-59 | — |
| Tipo | `tipo` | EFECTIVO_COBRADOR / CAJA_CENTRAL / CUENTA_BANCARIA / SALDO_CREDITO | código de catálogo | sí | capturado | inmutable | — | coincide con dueño | — |
| Usuario cobrador | `usuario_cobrador_id` | Dueño | identificador | no* | capturado | inmutable | — | exactamente uno de 4 | — |
| Caja central | `caja_central_id` | Dueño | identificador | no* | capturado | inmutable | — | exactamente uno de 4 | — |
| Cuenta bancaria | `cuenta_bancaria_id` | Dueño | identificador | no* | capturado | inmutable | — | exactamente uno de 4 | — |
| Crédito | `credito_id` | Dueño | identificador | no* | capturado | inmutable | — | exactamente uno de 4 | — |
| Saldo actual | `saldo_actual_centavos` | Proyección | importe en centavos | sí | derivado | proyectado | 0 | solo vía MovimientoCuenta | financiera |
| Versión | `version` | Optimistic lock | versión | sí | generado | proyectado | 1 | misma TX que movimiento | — |
| Activa | `activa` | Si aplica | booleano | sí | capturado | mutable | true | unicidad activa por tipo | — |

\*Exactamente una de las cuatro FK de dueño informada (D-59).

## 3. `movimientos_cuenta`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Operación | `operacion_financiera_id` | Folio | identificador | sí | capturado | inmutable | — | 1:N | financiera |
| Cuenta | `cuenta_operativa_id` | Cuenta | identificador | sí | capturado | inmutable | — | — | financiera |
| Importe con signo | `importe_con_signo_centavos` | + aumenta / − disminuye | importe en centavos | sí | capturado | inmutable | — | ≠ 0 | financiera |
| Concepto | `concepto` | Distingue asientos | código de catálogo | no* | capturado | inmutable | — | *oblig. si varios en misma cuenta | financiera |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 4. `idempotencias_operacion`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-55, D-68 | — |
| Ámbito | `ambito` | Comando funcional | código de catálogo | sí | capturado | inmutable | — | catálogo D-68 | — |
| Clave | `clave` | Clave cliente | texto corto | sí | capturado | inmutable | — | UK (ambito, clave) | — |
| Huella solicitud | `huella_solicitud` | Hash payload | texto corto | sí | generado | inmutable | — | misma clave ≠ huella → error | — |
| Estado | `estado` | EN_PROCESO / COMPLETADA / FALLIDA | código de catálogo | sí | capturado | mutable | EN_PROCESO | — | — |
| Operación | `operacion_financiera_id` | Resultado | identificador | no | generado | mutable | — | opcional hasta completar | financiera |
| Referencia resultado | `referencia_resultado` | Cache resultado | texto largo | no | generado | mutable | — | — | — |
| Error controlado | `error_controlado` | Si FALLIDA | texto largo | no | generado | mutable | — | — | — |
| Creado en | `creado_en` | Inicio | fecha y hora | sí | generado | inmutable | ahora | — | auditoría |
| Actualizado en | `actualizado_en` | Último cambio | fecha y hora | sí | generado | mutable | ahora | — | — |
| Finalizado en | `finalizado_en` | Fin | fecha y hora | no | generado | mutable | — | — | — |

## 5. `contrapartes_externas`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-34, D-48 | — |
| Tipo | `tipo` | APORTANTE / PROVEEDOR / CLIENTE / … | código de catálogo | sí | capturado | mutable | — | — | — |
| Nombre | `nombre` | Nombre | texto corto | sí | capturado | mutable | — | — | sensible |
| Referencia | `referencia` | Ref. externa | texto corto | no | capturado | mutable | — | — | — |
| Documento | `documento` | Doc. opcional | texto corto | no | capturado | mutable | — | — | sensible |
| Teléfono | `telefono` | Teléfono | texto corto | no | capturado | mutable | — | — | sensible |
| Cliente | `cliente_id` | Si tipo CLIENTE | identificador | no* | capturado | mutable | — | *oblig. si CLIENTE | — |
| Observaciones | `observaciones` | Texto | texto largo | no | capturado | mutable | — | — | — |
| Estado | `estado` | Activa / inactiva | código de catálogo | sí | capturado | mutable | ACTIVA | sin borrado físico | — |

---

## 6. `eventos_auditoria`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-16, D-60 | auditoría |
| Usuario | `usuario_id` | Actor | identificador | no | capturado | inmutable | — | — | auditoría |
| Dispositivo | `dispositivo_id` | Origen | identificador | no | capturado | inmutable | — | — | auditoría |
| Módulo | `modulo` | Módulo | texto corto | sí | capturado | inmutable | — | — | auditoría |
| Entidad tipo | `entidad_tipo` | Tipo lógico | texto corto | sí | capturado | inmutable | — | ref. lógica | auditoría |
| Entidad id | `entidad_id` | Id lógico | identificador | sí | capturado | inmutable | — | app valida existencia | auditoría |
| Operación financiera | `operacion_financiera_id` | FK real opcional | identificador | no | capturado | inmutable | — | D-60 | financiera |
| Valor anterior | `valor_anterior` | Snapshot | texto largo | no | capturado | inmutable | — | — | auditoría |
| Valor nuevo | `valor_nuevo` | Snapshot | texto largo | no | capturado | inmutable | — | — | auditoría |
| Motivo | `motivo` | Motivo | texto largo | no | capturado | inmutable | — | — | auditoría |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 7. `evidencias_operacion`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-41 | — |
| Operación | `operacion_financiera_id` | FK | identificador | sí | capturado | inmutable | — | — | financiera |
| Tipo sustento | `tipo_sustento` | SIN_DOCUMENTO / CON_DOCUMENTO | código de catálogo | sí | capturado | inmutable | — | — | — |
| Tipo evidencia | `tipo_evidencia` | Clasificación | código de catálogo | sí | capturado | inmutable | — | — | — |
| Archivo | `archivo_privado` | Ruta | referencia a archivo privado | no* | capturado | inmutable | — | *oblig. si CON_DOCUMENTO | sensible |
| Texto | `texto` | Observación | texto largo | no | capturado | inmutable | — | — | — |
| Usuario | `usuario_id` | Quién adjunta | identificador | sí | capturado | inmutable | — | — | auditoría |
| Registrado en | `registrado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 8. `parametros_sistema`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Clave | `clave` | Parámetro | texto corto | sí | capturado | inmutable | — | versionada | — |
| Valor | `valor` | Valor | texto largo | sí | capturado | inmutable | — | nueva versión al cambiar | — |
| Vigente desde | `vigente_desde` | Inicio | fecha y hora | sí | capturado | inmutable | — | D-12, D-13, D-37 | — |
| Vigente hasta | `vigente_hasta` | Fin | fecha y hora | no | capturado | mutable | — | — | — |
| Zona horaria proceso | `zona_horaria` | Si aplica atraso | texto corto | no | capturado | inmutable | — | D-37 | — |

## 9. `dias_festivos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-03, D-53 | — |
| Fecha | `fecha` | Día festivo | fecha | sí | capturado | inmutable | — | UK | — |
| Descripción | `descripcion` | Nombre | texto corto | sí | capturado | mutable | — | — | — |
| Activo | `activo` | Vigente | booleano | sí | capturado | mutable | true | no regenera históricas automáticas | — |

---

## 10. `ejecuciones_proceso_atraso`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-37 | — |
| Inicio | `iniciado_en` | Start | fecha y hora | sí | generado | inmutable | ahora | no es OF | — |
| Fin | `finalizado_en` | End | fecha y hora | no | generado | mutable | — | — | — |
| Disparador | `disparador` | PROGRAMADO / MANUAL | código de catálogo | sí | capturado | inmutable | — | — | — |
| Usuario | `usuario_id` | Si manual | identificador | no | capturado | inmutable | — | — | auditoría |
| Créditos procesados | `creditos_procesados` | Conteo | número entero | sí | derivado | inmutable | 0 | — | — |
| Resultado | `resultado` | OK / ERROR parcial | código de catálogo | sí | generado | inmutable | — | no mueve dinero | — |
| Detalle | `detalle` | Log resumido | texto largo | no | generado | inmutable | — | — | — |

## 11. `lotes_carga_inicial`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | D-56, D-51 | — |
| Referencia lote | `referencia_lote` | Código lote | texto corto | sí | capturado | inmutable | — | UK | — |
| Estado | `estado` | ABIERTO / EN_VALIDACION / CERRADO / CANCELADO | código de catálogo | sí | capturado | mutable | ABIERTO | bloquea CARGA tras 1er cierre | — |
| Administrador | `administrador_id` | Responsable | identificador | sí | capturado | inmutable | — | — | auditoría |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Abierto en | `abierto_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | — | — |
| Cerrado en | `cerrado_en` | Fecha | fecha y hora | no | capturado | mutable | — | — | — |

---

## Referencias

- `docs/04-database/modelo-movimientos-financieros.md`
- `docs/04-database/catalogo-operaciones-financieras.md`
- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`
