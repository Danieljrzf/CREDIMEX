# CREDIMEX — Catálogo de entidades (modelo conceptual)

**Estado:** Aprobado (Fase 3A)
**Alcance:** Modelo conceptual. Sin tipos SQL.
**Fuentes:** Documento maestro v1.2, Decisiones v1.3, Decisiones modelo v1.4,
Decisiones modelo v1.5 (D-33 a D-50), casos de uso y reglas de caja/jornada.

**Modelo lógico (Fase 3A.2):** la materialización en tablas, columnas, claves
y ERD está en D-54 a D-68 y en
`docs/07-decisions/CREDIMEX_Decisiones_Modelo_Logico_v1.6.md`,
`docs/04-database/inventario-tablas-logicas.md` y diccionarios asociados.
Este catálogo conceptual **no se altera** por v1.6.

Convención de mutabilidad:

- **mutable** — se actualiza in place (estado, proyecciones, datos vigentes);
- **versionada** — nueva versión; historial conservado;
- **append-only** — no se edita ni borra; correcciones por nuevo registro o reverso.

---

## 1. Identidad y seguridad

### Usuario

- **Objetivo:** Persona con acceso al sistema.
- **Datos conceptuales:** identificación, nombre, credenciales, estado, rol
  principal, dispositivo vinculado.
- **Relaciones:** N:1 `Rol`; 1:N dispositivos/sesiones; 1:N jornadas (si
  cobrador); 1:1 `CuentaOperativa` `EFECTIVO_COBRADOR` (si cobrador).
- **Mutabilidad:** mutable.
- **Reglas:** un rol principal en V1 (D-27); no eliminación física si tiene
  movimientos; bloqueo impide login.

### Rol

- **Objetivo:** Perfil de permisos (cobrador, supervisor, administrador).
- **Datos conceptuales:** código, nombre.
- **Relaciones:** 1:N usuarios; N:M permisos vía `RolPermiso`.
- **Mutabilidad:** catálogo mutable.
- **Reglas:** D-27.

### Permiso

- **Objetivo:** Capacidad atómica por módulo/operación.
- **Datos conceptuales:** código, módulo, descripción.
- **Relaciones:** N:M roles vía `RolPermiso`.
- **Mutabilidad:** catálogo.
- **Reglas:** matriz del maestro + D-16 (alcance de auditoría).

### RolPermiso

- **Objetivo:** Asignación rol–permiso.
- **Datos conceptuales:** rol, permiso.
- **Relaciones:** intermedia N:M.
- **Mutabilidad:** mutable.
- **Reglas:** D-27.

### Dispositivo

- **Objetivo:** Vínculo de sesión al teléfono.
- **Datos conceptuales:** identificador, usuario, estado, fechas.
- **Relaciones:** N:1 usuario.
- **Mutabilidad:** mutable.
- **Reglas:** sesiones vinculadas a dispositivos.

### SesionToken

- **Objetivo:** Autenticación API (Android V1 y web futura).
- **Datos conceptuales:** token, expiración, dispositivo, estado.
- **Relaciones:** N:1 usuario/dispositivo.
- **Mutabilidad:** revocable (no append-only rígido).
- **Reglas:** fuera del alcance de auditoría parcial del supervisor (D-16).

---

## 2. Clientes y documentos

### Cliente

- **Objetivo:** Titular de créditos.
- **Datos conceptuales:** nombre, teléfono principal, estado; sin
  `ruta_actual`, domicilio ni GPS vigentes duplicados.
- **Relaciones:** 1:N contactos, referencias, documentos, créditos,
  restricciones; 1:N `AsignacionClienteRuta`; 1:N domicilios/ubicaciones.
- **Mutabilidad:** mutable + historial en entidades hijas.
- **Reglas:** RN-CLI; D-19 (sin CURP/OCR obligatorio).

### ContactoAlternativo

- **Objetivo:** Contacto obligatorio.
- **Datos conceptuales:** nombre, teléfono, relación; vigencia.
- **Relaciones:** N:1 cliente.
- **Mutabilidad:** versionada / historial.
- **Reglas:** RN-CLI-002.

### Referencia

- **Objetivo:** Referencia del cliente.
- **Datos conceptuales:** nombre, teléfono, relación, observaciones,
  dirección opcional.
- **Relaciones:** N:1 cliente.
- **Mutabilidad:** versionada / historial.
- **Reglas:** al menos una obligatoria.

### DomicilioUbicacion

- **Objetivo:** Domicilio y GPS del cliente (fuente de verdad).
- **Datos conceptuales:** dirección, latitud, longitud, fecha captura,
  vigencia.
- **Relaciones:** N:1 cliente.
- **Mutabilidad:** versionada; una vigente.
- **Reglas:** GPS obligatorio; cambio con motivo.

### DocumentoCliente

- **Objetivo:** INE y comprobante de domicilio.
- **Datos conceptuales:** tipo, metadatos, ruta de archivo privado,
  capturista, vigencia.
- **Relaciones:** N:1 cliente.
- **Mutabilidad:** append-only (sustitución = nuevo; anterior conservado).
- **Reglas:** no eliminación física.

### ConfirmacionNoDuplicado

- **Objetivo:** Registro de confirmación “otra persona” ante coincidencias.
- **Datos conceptuales:** cliente, candidatos, usuario, fecha.
- **Relaciones:** N:1 cliente.
- **Mutabilidad:** append-only.
- **Reglas:** RN-CLI-006, D-19.

---

## 3. Rutas y asignaciones

### Ruta

- **Objetivo:** Zona de cobranza.
- **Datos conceptuales:** nombre libre, estado.
- **Relaciones:** 1:N `AsignacionRutaCobrador`; 1:N `AsignacionClienteRuta`.
- **Mutabilidad:** mutable.
- **Reglas:** RN-RUT.

### AsignacionRutaCobrador

- **Objetivo:** Cobrador titular histórico/vigente de una ruta.
- **Datos conceptuales:** ruta, cobrador, vigencia, motivo.
- **Relaciones:** N:1 ruta; N:1 usuario.
- **Mutabilidad:** versionada.
- **Reglas:** evita polimorfismo; un titular vigente por ruta.

### AsignacionClienteRuta

- **Objetivo:** Ruta a la que pertenece el cliente (fuente de verdad).
- **Datos conceptuales:** cliente, ruta, vigencia, motivo.
- **Relaciones:** N:1 cliente; N:1 ruta.
- **Mutabilidad:** versionada.
- **Reglas:** no usar `Cliente.ruta_actual`.

### AsignacionTemporalCobranza

- **Objetivo:** Cobro temporal por otro cobrador.
- **Datos conceptuales:** cobrador, alcance (cliente/crédito), fechas,
  motivo, vigencia.
- **Relaciones:** N:1 cobrador; referencia a cliente y/o crédito.
- **Mutabilidad:** versionada.
- **Reglas:** un crédito disponible para un cobrador a la vez.

---

## 4. Planes y créditos

### PlanCredito

- **Objetivo:** Catálogo de plazos y tasas.
- **Datos conceptuales:** plazo en cuotas programadas, tasa, estado.
- **Relaciones:** 1:N `VersionPlan`.
- **Mutabilidad:** mutable (activar/desactivar).
- **Reglas:** D-01.

### VersionPlan

- **Objetivo:** Snapshot de plan aplicable a solicitudes nuevas.
- **Datos conceptuales:** tasa, plazo, vigencia.
- **Relaciones:** N:1 plan.
- **Mutabilidad:** append-only.
- **Reglas:** cambios no afectan créditos previos.

### SolicitudCredito

- **Objetivo:** Pedido antes del desembolso.
- **Datos conceptuales:** monto, plazo, cálculos, estado, cliente.
- **Relaciones:** N:1 cliente; 1:N autorizaciones; 0..1 crédito si
  `APROBADA`.
- **Mutabilidad:** mutable de estado.
- **Reglas:** no cuenta en máximo de cinco; sin estado `DESEMBOLSADA`.

### Autorizacion

- **Objetivo:** Decisión sobre solicitud o renovación.
- **Datos conceptuales:** usuario, resultado, monto, observaciones, fecha.
- **Relaciones:** N:1 solicitud (u operación relacionada).
- **Mutabilidad:** append-only.
- **Reglas:** D-12 autoautorización dentro de límite.

### Credito

- **Objetivo:** Préstamo autorizado/desembolsado.
- **Datos conceptuales:** estado principal; `saldo_inicial_centavos`;
  `saldo_actual_centavos`; proyecciones de atraso (D-28); versión de
  condiciones vigente.
- **Relaciones:** N:1 cliente; 1:N desembolsos; 1:N calendarios;
  1:N versiones de condiciones; 1:1 `CuentaOperativa` `SALDO_CREDITO`.
- **Mutabilidad:** mutable (estado y proyecciones); condiciones versionadas.
- **Reglas:** máximo cinco = `ACTIVO` y saldo > 0; D-21, D-23, D-28.

### VersionCondicionesCredito

- **Objetivo:** Snapshot definitivo de condiciones (autorización o
  reestructura).
- **Datos conceptuales:** monto, plazo, tasa, interés, total, cuota,
  comisión, fechas.
- **Relaciones:** N:1 crédito.
- **Mutabilidad:** append-only.
- **Reglas:** D-10; saldo inicial = monto + interés.

### Desembolso

- **Objetivo:** Intento/entrega del préstamo.
- **Datos conceptuales:** modalidad, importes de principal/comisión/primer
  pago retenido, `efectivo_neto`, estado, reserva asociada.
- **Relaciones:** N:1 crédito; 1 operación `DESEMBOLSO_CREDITO` al
  confirmar; 0..1 `PRIMER_PAGO_RETENIDO` hija; 0..1 comisión liquidada.
- **Mutabilidad:** mutable de estado hasta terminal; registro append-only de
  hechos confirmados.
- **Reglas:** D-17, D-26, D-31, D-35, D-36; máximo un `CONFIRMADO` por
  crédito; movimientos brutos de principal y comisión.

### Comision

- **Objetivo:** Comisión del préstamo liquidada en el desembolso.
- **Datos conceptuales:** importe, modalidad, fecha, medio.
- **Relaciones:** 1:1 desembolso confirmado / crédito.
- **Mutabilidad:** append-only.
- **Reglas:** D-11; no pendiente; no forma parte del saldo.

### Renovacion

- **Objetivo:** Vínculo crédito liquidado → nuevo crédito.
- **Datos conceptuales:** crédito origen, crédito destino, fecha.
- **Relaciones:** 1:1 origen; 1:1 destino.
- **Mutabilidad:** append-only.
- **Reglas:** RN-REN; origen liquidado.

### Reestructuracion

- **Objetivo:** Evento de cambio de condiciones sobre saldo pendiente.
- **Datos conceptuales:** saldo base, nuevo plazo/tasa, `nuevo_interes`,
  motivo, autorizador.
- **Relaciones:** N:1 crédito; 1:1 `OperacionFinanciera`
  `REESTRUCTURACION_CREDITO`; genera nueva `VersionCondicionesCredito` y
  nuevo `Calendario`.
- **Mutabilidad:** append-only.
- **Reglas:** D-10, D-44; no es estado del crédito; solo mueve
  `SALDO_CREDITO + nuevo_interes`.

### CastigoCredito

- **Objetivo:** Marca de castigo con snapshot de saldo.
- **Datos conceptuales:** motivo, saldo snapshot, usuario, fecha.
- **Relaciones:** 1:1 crédito.
- **Mutabilidad:** append-only.
- **Reglas:** UC-26; D-24.

### RecuperacionCreditoCastigado

- **Objetivo:** Recuperación posterior al castigo.
- **Datos conceptuales:** importe, medio, fecha, operación.
- **Relaciones:** N:1 crédito castigado; 1:1 operación financiera.
- **Mutabilidad:** append-only.
- **Reglas:** D-24; no reactiva ni habilita renovación.

---

## 5. Calendarios y cuotas

### Calendario

- **Objetivo:** Versión de calendario de cuotas del crédito.
- **Datos conceptuales:** estado (`VIGENTE` / `REEMPLAZADO`), fechas.
- **Relaciones:** N:1 crédito; 1:N cuotas.
- **Mutabilidad:** versionada.
- **Reglas:** reestructura reemplaza el anterior.

### CuotaProgramada

- **Objetivo:** Cuota exigible programada.
- **Datos conceptuales:** número, fecha programada, importe centavos,
  estado, montos cubiertos.
- **Relaciones:** N:1 calendario; N:M pagos vía `AplicacionPagoCuota`.
- **Mutabilidad:** mutable de cobertura/estado; importe fijo.
- **Reglas:** sin filas en domingo/festivo; D-05, D-06, D-07, D-08.

### AplicacionPagoCuota

- **Objetivo:** Cobertura FIFO de cuotas por pago.
- **Datos conceptuales:** pago, cuota, importe aplicado (centavos).
- **Relaciones:** N:1 pago; N:1 cuota.
- **Mutabilidad:** append-only.
- **Reglas:** base del atraso derivado.

---

## 6. Pagos, transferencias y tickets

### Pago

- **Objetivo:** Abono al crédito.
- **Datos conceptuales:** importe, medio (`EFECTIVO` | `TRANSFERENCIA` |
  `RETENIDO_DESEMBOLSO`), folio, saldos ant/nuevo, estado, clave de
  idempotencia.
- **Relaciones:** N:1 crédito; 1:N aplicaciones; 0..1 reverso; 0..N tickets;
  1:1 `OperacionFinanciera` (`PAGO` o `PRIMER_PAGO_RETENIDO`).
- **Mutabilidad:** append-only (estado a `REVERTIDO` sin borrar).
- **Reglas:** RN-PAG; D-49; no modificar para cuadrar caja.

### ReversoPago

- **Objetivo:** Corrección de pago incorrecto.
- **Datos conceptuales:** pago original, motivo, usuario, operación.
- **Relaciones:** 1:1 pago.
- **Mutabilidad:** append-only.
- **Reglas:** no usar para cuadrar caja.

### TransferenciaBancaria

- **Objetivo:** Pago verificado en cuenta receptora.
- **Datos conceptuales:** cuenta, referencia, importe, validador.
- **Relaciones:** N:1 crédito; operación financiera; no afecta
  `EFECTIVO_COBRADOR`.
- **Mutabilidad:** append-only.
- **Reglas:** RN-TRA.

### CuentaReceptora

- **Objetivo:** Catálogo de cuentas bancarias de CREDIMEX.
- **Datos conceptuales:** banco, referencia, estado.
- **Relaciones:** 1:N transferencias; 1:1 `CuentaOperativa`
  `CUENTA_BANCARIA`.
- **Mutabilidad:** mutable.
- **Reglas:** RF-TRA.

### Ticket

- **Objetivo:** Comprobante de pago.
- **Datos conceptuales:** folio, datos RN-TIC, estado impresión; para pago
  retenido: leyenda «PRIMER PAGO RETENIDO», medio `RETENIDO_DESEMBOLSO` y
  desembolso relacionado (D-45).
- **Relaciones:** N:1 pago; 1:N reimpresiones.
- **Mutabilidad:** append-only.
- **Reglas:** solo tras confirmación del servidor.

### ReimpresionTicket

- **Objetivo:** Reimpresión marcada.
- **Datos conceptuales:** leyenda REIMPRESIÓN, usuario, fecha.
- **Relaciones:** N:1 ticket.
- **Mutabilidad:** append-only.
- **Reglas:** auditada.

---

## 7. Cobranza

### VisitaSinPago

- **Objetivo:** Evidencia de visita sin abono.
- **Datos conceptuales:** motivo, observaciones, fecha.
- **Relaciones:** N:1 crédito/cliente.
- **Mutabilidad:** append-only.
- **Reglas:** UC-06.

### PromesaPago

- **Objetivo:** Compromiso de pago.
- **Datos conceptuales:** fecha prometida, monto.
- **Relaciones:** N:1 visita o crédito.
- **Mutabilidad:** mutable / historial.
- **Reglas:** UC-06.

---

## 8. Caja del cobrador, caja central, entregas y cortes

### CajaCentral

- **Objetivo:** Contenedor de tesorería operativa.
- **Datos conceptuales:** `codigo` (único), `nombre`, `estado` (D-29, D-39).
- **Relaciones:** 1:1 `CuentaOperativa` `CAJA_CENTRAL`; 1:N jornadas/cortes.
- **Mutabilidad:** mutable.
- **Reglas:** varias filas permitidas; solo una `ACTIVA` en V1; sin
  `sucursal_id`; inactivación solo con saldo cero y sin jornada abierta.

### JornadaCobrador

- **Objetivo:** Agrupar operaciones del cobrador por fecha operativa.
- **Datos conceptuales:** cobrador, fecha, estado, saldo inicial autorizado,
  noches de permanencia (racha con efectivo conservado > 0).
- **Relaciones:** N:1 usuario cobrador; 1:N operaciones del día; 1:N cortes;
  0..N `ExcepcionPermanenciaEfectivo`.
- **Mutabilidad:** mutable de estado.
- **Reglas:** D-22; creación perezosa; UK (cobrador, fecha); D-38/D-46.

### JornadaCajaCentral

- **Objetivo:** Día operativo de la caja central.
- **Datos conceptuales:** caja, fecha, saldo inicial (snapshot del corte
  anterior), estado.
- **Relaciones:** N:1 caja central; 1:N operaciones; 1:N cortes.
- **Mutabilidad:** mutable de estado.
- **Reglas:** D-40; creación perezosa en `ABIERTA` con la primera operación;
  UK (`caja_central`, `fecha_operativa`); no se persiste `PENDIENTE`.

### EntregaEfectivo

- **Objetivo:** Transferencia física con doble confirmación (cobrador→central
  o fondo→cobrador).
- **Datos conceptuales:** motivo de entrega (si fondo), declarado, recibido,
  folio, estado; `operacion_relacionada_id` opcional.
- **Relaciones:** operación financiera; opcional incidencia.
- **Mutabilidad:** mutable hasta confirmar/cancelar.
- **Reglas:** UC-09, UC-24; D-47, D-50; solo se mueve el importe recibido.

### MotivoEntregaFondo

- **Objetivo:** Catálogo de motivo de la entrega al cobrador (D-50).
- **Datos conceptuales:**
  `FONDO_INICIAL` | `FONDO_ADICIONAL` | `PARA_DESEMBOLSO` |
  `OPERACION_GENERAL` | `OTRO`.
- **Mutabilidad:** catálogo.
- **Reglas:** reemplaza `OrigenComercialFondo` en el flujo ordinario; la
  entrega siempre sale de `CAJA_CENTRAL`.

### Gasto

- **Objetivo:** Gasto autorizado descontado de efectivo.
- **Datos conceptuales:** concepto, importe, autorizador.
- **Relaciones:** operación; cuenta afectada (cobrador o central).
- **Mutabilidad:** append-only.
- **Reglas:** cobrador no autoautoriza.

### Aportacion / IngresoExtraordinario / DepositoBancarioManual / Retiro

- **Objetivo:** Movimientos de tesorería de UC-25.
- **Datos conceptuales:** importe, documento, motivo, cuenta de custodia;
  contraparte externa opcional.
- **Relaciones:** operación financiera; `CAJA_CENTRAL` o `CUENTA_BANCARIA`.
- **Mutabilidad:** append-only.
- **Reglas:** D-25, D-34; sin integración bancaria automática.

### IncidenciaCaja

- **Objetivo:** Faltante o sobrante.
- **Datos conceptuales:** tipo, importe, responsable, estado, origen.
- **Relaciones:** entrega o corte; recuperaciones/ajustes.
- **Mutabilidad:** append-only + resolución.
- **Reglas:** `cash-differences.md`.

### CorteCobrador / CorteCajaCentral

- **Objetivo:** Conciliación diaria.
- **Datos conceptuales:** esperado, contado, diferencia, conservado,
  entregado, versión.
- **Relaciones:** N:1 jornada; 0..N incidencias.
- **Mutabilidad:** versionada (reaperturas).
- **Reglas:** RN-COR; UC-25.

### ReservaEfectivo

- **Objetivo:** Reservar disponible del cobrador ante desembolso pendiente.
- **Datos conceptuales:** cobrador, desembolso, importe, estado
  (activa / liberada / consumida).
- **Relaciones:** N:1 desembolso; N:1 usuario cobrador.
- **Mutabilidad:** mutable de estado.
- **Reglas:** D-26; no genera `MovimientoCuenta` definitivo.

---

## 9. Clientes restringidos

### RestriccionCliente

- **Objetivo:** Impedir nuevos créditos / renovaciones / reestructuras con
  dinero nuevo.
- **Datos conceptuales:** motivo, créditos relacionados, revisión, estado.
- **Relaciones:** N:1 cliente.
- **Mutabilidad:** mutable de estado.
- **Reglas:** RN-RES; no auto-retira; castigo no la crea automáticamente.

---

## 10. Libro operativo

### OperacionFinanciera

- **Objetivo:** Folio lógico que agrupa movimientos e idempotencia.
- **Datos conceptuales:** tipo (catálogo cerrado D-33), folio, clave
  idempotencia, actores, fecha operativa, estado, motivo, medio (si aplica),
  `operacion_padre_id` opcional, `incidencia_caja_id` opcional,
  `jornada_cobrador_id` / `jornada_caja_central_id` opcionales, contraparte
  opcional con snapshot de nombre y referencia; en carga inicial:
  fecha/hora de corte, responsable del conteo, `referencia_lote` (D-51).
- **Relaciones:** 1:N `MovimientoCuenta`; 0..N `EvidenciaOperacion`;
  autorrelación padre/hija; referencia a entidad de dominio.
- **Mutabilidad:** append-only de hechos; estado lógico controlado.
- **Reglas:** D-33 a D-50; ver
  `catalogo-operaciones-financieras.md`.

### CuentaOperativa

- **Objetivo:** Cuenta de saldo operativo.
- **Datos conceptuales:** tipo (`EFECTIVO_COBRADOR`, `CAJA_CENTRAL`,
  `CUENTA_BANCARIA`, `SALDO_CREDITO`), dueño, `saldo_actual_centavos`,
  versión.
- **Relaciones:** 1:N movimientos; dueño según tipo (usuario, caja,
  cuenta receptora, crédito).
- **Mutabilidad:** mutable proyección; historial vía movimientos.
- **Reglas:** D-21, D-32; libro operativo no fiscal.

### MovimientoCuenta

- **Objetivo:** Asiento append-only con signo.
- **Datos conceptuales:** operación, cuenta, importe con signo (centavos),
  `concepto` (opcional; obligatorio cuando hay varios asientos sobre la
  misma cuenta en la misma operación), metadatos.
- **Relaciones:** N:1 operación; N:1 cuenta.
- **Mutabilidad:** append-only.
- **Reglas:** positivo aumenta; negativo disminuye; transferencias ≥2
  movimientos; D-35.

### IdempotenciaOperacion

- **Objetivo:** Evitar duplicados por reintento de red.
- **Datos conceptuales:** clave, resultado, operación.
- **Relaciones:** 1:1 operación o resultado cacheado.
- **Mutabilidad:** append-only / upsert controlado.
- **Reglas:** maestro §4.4.

### ContraparteExterna

- **Objetivo:** Persona o institución externa a las operaciones de
  tesorería (D-34, D-48).
- **Datos conceptuales:** tipo (`APORTANTE` | `PROVEEDOR` | `CLIENTE` |
  `INSTITUCION_FINANCIERA_EXTERNA` | `OTRO`), nombre, referencia,
  documento opcional, teléfono opcional, observaciones, estado;
  `cliente_id` obligatorio si tipo `CLIENTE`.
- **Relaciones:** 1:N operaciones (opcional); N:1 `Cliente` si aplica.
- **Mutabilidad:** mutable; sin borrado físico.
- **Reglas:** cuentas bancarias propias = `CuentaReceptora`, no contraparte.

### ExcepcionPermanenciaEfectivo

- **Objetivo:** Autorización auditada de una cuarta noche (D-38, D-46,
  UC-27).
- **Datos conceptuales:** cobrador, importe autorizado, motivo, fecha
  límite, observaciones, administrador, jornada/corte relacionados, estado.
- **Relaciones:** N:1 cobrador; 0..1 jornada; 0..1 corte.
- **Mutabilidad:** mutable de estado.
- **Reglas:** solo una cuarta noche; no quinta; no altera disponible.

### EvidenciaOperacion

- **Objetivo:** Soporte documental o textual de una operación (D-41).
- **Datos conceptuales:** `tipo_sustento` (`SIN_DOCUMENTO` |
  `CON_DOCUMENTO`), tipo de evidencia, ruta de archivo privado opcional,
  texto, usuario, fecha.
- **Relaciones:** N:1 `OperacionFinanciera`.
- **Mutabilidad:** append-only.
- **Reglas:** archivo obligatorio si `CON_DOCUMENTO`.

### EjecucionProcesoAtraso

- **Objetivo:** Bitácora del recálculo diario o manual de atraso (D-37).
- **Datos conceptuales:** inicio, fin, disparador (programado/manual),
  usuario, créditos procesados, resultado.
- **Relaciones:** —
- **Mutabilidad:** append-only.
- **Reglas:** no es `OperacionFinanciera`; no mueve dinero.

---

## 11. Auditoría y configuración

### EventoAuditoria

- **Objetivo:** Trazabilidad de cambios sensibles.
- **Datos conceptuales:** usuario, dispositivo, módulo, valor anterior/nuevo,
  motivo, entidad.
- **Relaciones:** polimórfica.
- **Mutabilidad:** append-only.
- **Reglas:** D-16.

### ParametroSistema

- **Objetivo:** Límites, horarios, máximos, incrementos.
- **Datos conceptuales:** clave, valor, vigencia (incluye hora y zona
  horaria del proceso diario de atraso).
- **Relaciones:** —
- **Mutabilidad:** versionada.
- **Reglas:** D-12, D-13, D-37, límite efectivo, noches.

### DiaFestivo

- **Objetivo:** Día sin cuota programada.
- **Datos conceptuales:** fecha, descripción.
- **Relaciones:** —
- **Mutabilidad:** mutable según D-53.
- **Reglas:** D-03, D-53; no genera `CuotaProgramada`.
  - Festivo **futuro:** vista previa; recalcular solo cuotas `PROGRAMADA`;
    conservar número, orden e importes; ajustar fechas futuras y fecha
    final; auditar; recalcular proyecciones.
  - Fecha **actual o pasada:** no regenerar automáticamente cuotas
    `PENDIENTE`, `PARCIALMENTE_CUBIERTA`, `CUBIERTA` o históricas; no
    modificar pagos, atrasos o semáforos anteriores; puede aplicar a
    calendarios nuevos; corrección histórica solo por proceso administrativo
    extraordinario y auditado.
