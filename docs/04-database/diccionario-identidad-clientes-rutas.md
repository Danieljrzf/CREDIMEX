# CREDIMEX — Diccionario: identidad, clientes y rutas

**Estado:** Aprobado (Fase 3A.2)
**Dominios:** 1–3
**Alcance:** Columnas conceptuales. Sin tipos SQL.
**Fuentes:** Inventario lógico, D-54 a D-68, catálogo de entidades.

## Convenciones

| Campo del diccionario | Significado |
|---|---|
| Tipo conceptual | identificador, texto corto/largo, fecha, fecha y hora, importe en centavos, porcentaje exacto, número entero, booleano, código de catálogo, versión, referencia a archivo privado, coordenada geográfica |
| Origen | capturado / generado / derivado |
| Mutabilidad | mutable / inmutable / proyectado |
| Clasificación | sensible / financiera / auditoría / — |

Columnas técnicas comunes omitidas en tablas repetitivas salvo donde aportan
regla: `id` (identificador, generado, inmutable), `created_at` / `updated_at`
cuando apliquen, conforme a D-84.

---

## 1. `usuarios`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Nombre | `nombre` | Nombre completo | texto corto | sí | capturado | mutable | — | — | sensible |
| Usuario login | `nombre_usuario` | Credencial de acceso | texto corto | sí | capturado | mutable | — | único | sensible |
| Hash credencial | `credencial_hash` | Secreto almacenado | texto corto | sí | generado | mutable | — | no en claro | sensible |
| Estado | `estado` | ACTIVO / BLOQUEADO | código de catálogo | sí | capturado | mutable | ACTIVO | D-27 | — |
| Rol | `rol_id` | Rol principal V1 | identificador | sí | capturado | mutable | — | Usuario N:1 Rol | — |
| Activo cobrador | `es_cobrador` | Marca operativa | booleano | sí | derivado | proyectado | false | según rol | — |

## 2. `roles`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Código | `codigo` | cobrador / supervisor / administrador | código de catálogo | sí | capturado | inmutable | — | único | — |
| Nombre | `nombre` | Etiqueta | texto corto | sí | capturado | mutable | — | — | — |
| Activo | `activo` | Disponible para asignación | booleano | sí | capturado | mutable | true | inactivación sin borrado | — |
| Creado | `created_at` | Alta de fila | fecha y hora | sí | generado | inmutable | sin default | D-84 | auditoría |
| Actualizado | `updated_at` | Último cambio | fecha y hora | sí | generado | mutable | sin default | D-84; actualización explícita | auditoría |

## 3. `permisos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Código | `codigo` | Capacidad atómica | código de catálogo | sí | capturado | inmutable | — | único | — |
| Módulo | `modulo` | Agrupación | texto corto | sí | capturado | mutable | — | — | — |
| Descripción | `descripcion` | Texto | texto largo | no | capturado | mutable | — | — | — |
| Activo | `activo` | Disponible para asignación | booleano | sí | capturado | mutable | true | inactivación sin borrado | — |
| Creado | `created_at` | Alta de fila | fecha y hora | sí | generado | inmutable | sin default | D-84 | auditoría |
| Actualizado | `updated_at` | Último cambio | fecha y hora | sí | generado | mutable | sin default | D-84; actualización explícita | auditoría |

## 4. `rol_permisos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Rol | `rol_id` | FK rol | identificador | sí | capturado | inmutable | — | N:M | — |
| Permiso | `permiso_id` | FK permiso | identificador | sí | capturado | inmutable | — | UK (rol, permiso) | — |
| Creado | `created_at` | Alta de asignación | fecha y hora | sí | generado | inmutable | sin default | D-84 | auditoría |

`rol_permisos` no utiliza `activo` ni `updated_at`; la matriz se modifica
creando o eliminando asignaciones de forma controlada.

## 5. `dispositivos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Usuario | `usuario_id` | Dueño | identificador | sí | capturado | inmutable | — | N:1 Usuario | — |
| Identificador dispositivo | `identificador_dispositivo` | ID del teléfono | texto corto | sí | capturado | inmutable | — | — | sensible |
| Estado | `estado` | Activo / revocado | código de catálogo | sí | capturado | mutable | ACTIVO | — | — |
| Vinculado en | `vinculado_en` | Fecha vínculo | fecha y hora | sí | generado | inmutable | ahora | — | auditoría |

## 6. `sesiones_token`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Usuario | `usuario_id` | Dueño | identificador | sí | capturado | inmutable | — | — | — |
| Dispositivo | `dispositivo_id` | Dispositivo | identificador | no | capturado | inmutable | — | — | — |
| Token hash | `token_hash` | Token almacenado | texto corto | sí | generado | inmutable | — | — | sensible |
| Expira en | `expira_en` | Caducidad | fecha y hora | sí | generado | inmutable | — | — | — |
| Estado | `estado` | Vigente / revocada | código de catálogo | sí | capturado | mutable | VIGENTE | D-16 fuera auditoría parcial | — |

---

## 7. `clientes`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Nombre | `nombre` | Nombre completo | texto corto | sí | capturado | mutable | — | RN-CLI | sensible |
| Teléfono principal | `telefono_principal` | Contacto | texto corto | sí | capturado | mutable | — | — | sensible |
| Estado | `estado` | ACTIVO / INACTIVO | código de catálogo | sí | capturado | mutable | ACTIVO | — | — |
| Observaciones | `observaciones` | Notas | texto largo | no | capturado | mutable | — | — | — |

Sin `ruta_actual`, domicilio ni GPS vigentes (viven en hijas).

## 8. `contactos_alternativos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | RN-CLI-002 | — |
| Nombre | `nombre` | Nombre contacto | texto corto | sí | capturado | mutable | — | — | sensible |
| Teléfono | `telefono` | Teléfono | texto corto | sí | capturado | mutable | — | — | sensible |
| Relación | `relacion` | Parentesco/relación | texto corto | sí | capturado | mutable | — | — | — |
| Vigente | `vigente` | Es el vigente | booleano | sí | capturado | mutable | true | historial | — |
| Vigente desde | `vigente_desde` | Inicio | fecha y hora | sí | generado | inmutable | ahora | — | — |
| Vigente hasta | `vigente_hasta` | Fin | fecha y hora | no | capturado | mutable | — | — | — |

## 9. `referencias`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | ≥1 obligatoria | — |
| Nombre | `nombre` | Nombre | texto corto | sí | capturado | mutable | — | — | sensible |
| Teléfono | `telefono` | Teléfono | texto corto | sí | capturado | mutable | — | — | sensible |
| Relación | `relacion` | Relación | texto corto | sí | capturado | mutable | — | — | — |
| Dirección | `direccion` | Dirección opcional | texto largo | no | capturado | mutable | — | — | sensible |
| Observaciones | `observaciones` | Notas | texto largo | no | capturado | mutable | — | — | — |
| Vigente | `vigente` | Vigencia | booleano | sí | capturado | mutable | true | — | — |

## 10. `domicilios_ubicaciones`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | 1 vigente | — |
| Dirección | `direccion` | Texto domicilio | texto largo | sí | capturado | inmutable | — | versionada | sensible |
| Latitud | `latitud` | GPS | coordenada geográfica | sí | capturado | inmutable | — | GPS obligatorio | sensible |
| Longitud | `longitud` | GPS | coordenada geográfica | sí | capturado | inmutable | — | GPS obligatorio | sensible |
| Capturado en | `capturado_en` | Fecha captura | fecha y hora | sí | generado | inmutable | ahora | — | auditoría |
| Motivo cambio | `motivo_cambio` | Motivo | texto largo | no | capturado | inmutable | — | al sustituir | auditoría |
| Vigente | `vigente` | Es el vigente | booleano | sí | capturado | mutable | true | solo uno vigente | — |

## 11. `documentos_cliente`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | — | — |
| Tipo | `tipo` | INE / comprobante | código de catálogo | sí | capturado | inmutable | — | — | — |
| Archivo | `archivo_privado` | Ruta privada | referencia a archivo privado | sí | capturado | inmutable | — | no borrado físico | sensible |
| Capturista | `capturista_id` | Usuario | identificador | sí | capturado | inmutable | — | — | auditoría |
| Capturado en | `capturado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

## 12. `confirmaciones_no_duplicado`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | Cliente confirmado | identificador | sí | capturado | inmutable | — | RN-CLI-006, D-19 | — |
| Candidatos | `candidatos_json` | IDs/nombres coincidentes | texto largo | sí | capturado | inmutable | — | snapshot | sensible |
| Usuario | `usuario_id` | Quien confirma | identificador | sí | capturado | inmutable | — | — | auditoría |
| Confirmado en | `confirmado_en` | Fecha | fecha y hora | sí | generado | inmutable | ahora | append-only | auditoría |

---

## 13. `rutas`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Nombre | `nombre` | Nombre libre | texto corto | sí | capturado | mutable | — | RN-RUT | — |
| Estado | `estado` | ACTIVA / INACTIVA | código de catálogo | sí | capturado | mutable | ACTIVA | — | — |

## 14. `asignaciones_ruta_cobrador`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Ruta | `ruta_id` | FK | identificador | sí | capturado | inmutable | — | 1 titular vigente | — |
| Cobrador | `cobrador_id` | Usuario cobrador | identificador | sí | capturado | inmutable | — | — | — |
| Estado | `estado` | VIGENTE / FINALIZADA / ANULADA | código de catálogo | sí | capturado | mutable | VIGENTE | — | — |
| Motivo | `motivo` | Motivo cambio | texto largo | no | capturado | inmutable | — | — | auditoría |
| Vigente desde | `vigente_desde` | Inicio | fecha y hora | sí | generado | inmutable | ahora | — | — |
| Vigente hasta | `vigente_hasta` | Fin | fecha y hora | no | capturado | mutable | — | — | — |

## 15. `asignaciones_cliente_ruta`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cliente | `cliente_id` | FK | identificador | sí | capturado | inmutable | — | 1 vigente | — |
| Ruta | `ruta_id` | FK | identificador | sí | capturado | inmutable | — | fuente de verdad | — |
| Estado | `estado` | VIGENTE / FINALIZADA / ANULADA | código de catálogo | sí | capturado | mutable | VIGENTE | — | — |
| Motivo | `motivo` | Motivo | texto largo | no | capturado | inmutable | — | — | auditoría |
| Vigente desde | `vigente_desde` | Inicio | fecha y hora | sí | generado | inmutable | ahora | — | — |
| Vigente hasta | `vigente_hasta` | Fin | fecha y hora | no | capturado | mutable | — | — | — |

## 16. `asignaciones_temporales_cobranza`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cobrador | `cobrador_id` | Quien cobra temporalmente | identificador | sí | capturado | inmutable | — | D-57 | — |
| Tipo alcance | `tipo_alcance` | RUTA / CLIENTE / CREDITO | código de catálogo | sí | capturado | inmutable | — | D-67 | — |
| Estado | `estado` | VIGENTE / FINALIZADA / ANULADA | código de catálogo | sí | capturado | mutable | VIGENTE | — | — |
| Motivo | `motivo` | Motivo | texto largo | sí | capturado | inmutable | — | — | auditoría |
| Vigente desde | `vigente_desde` | Inicio | fecha y hora | sí | capturado | inmutable | — | — | — |
| Vigente hasta | `vigente_hasta` | Fin previsto | fecha y hora | no | capturado | mutable | — | — | — |
| Creado por | `creado_por_id` | Usuario | identificador | sí | capturado | inmutable | — | — | auditoría |

## 17. `asignaciones_temporales_rutas`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cabecera | `asignacion_temporal_id` | FK cabecera | identificador | sí | capturado | inmutable | — | solo si tipo RUTA | — |
| Ruta | `ruta_id` | Ruta asignada | identificador | sí | capturado | inmutable | — | UK (cabecera, ruta) | — |

## 18. `asignaciones_temporales_clientes`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cabecera | `asignacion_temporal_id` | FK cabecera | identificador | sí | capturado | inmutable | — | solo si tipo CLIENTE | — |
| Cliente | `cliente_id` | Cliente asignado | identificador | sí | capturado | inmutable | — | UK (cabecera, cliente) | — |

## 19. `asignaciones_temporales_creditos`

| Lógico | Técnico | Significado | Tipo | Obl. | Origen | Mut. | Default | Regla | Clasif. |
|---|---|---|---|---|---|---|---|---|---|
| Identificador | `id` | PK | identificador | sí | generado | inmutable | — | — | — |
| Cabecera | `asignacion_temporal_id` | FK cabecera | identificador | sí | capturado | inmutable | — | solo si tipo CREDITO | — |
| Crédito | `credito_id` | Crédito asignado | identificador | sí | capturado | inmutable | — | UK (cabecera, crédito); un cobrador a la vez | — |

---

## Referencias

- `docs/04-database/inventario-tablas-logicas.md`
- `docs/04-database/diccionario-creditos-calendarios-pagos.md`
