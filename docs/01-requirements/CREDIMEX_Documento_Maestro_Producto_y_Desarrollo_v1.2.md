# CREDIMEX — Documento Maestro de Producto, Requerimientos y Desarrollo

**Versión:** 1.2  
**Fecha:** 24 de julio de 2026  
**Estado:** Línea base funcional y técnica para iniciar modelado, prototipos y construcción por fases  
**Plataformas:** Android en v1; API central; panel web administrativo planificado para v2

---

## Control de cambios

| Versión | Contenido |
|---|---|
| 1.0 | Requerimientos, reglas de negocio y alcance inicial |
| 1.1 | Casos de uso y diagramas de procesos |
| 1.2 | Arquitectura, estrategia con Cursor y preparación del panel web futuro |

---

# PARTE I — Requerimientos y reglas de negocio

## 1. Propósito del documento

Este documento establece la línea base de requerimientos para el desarrollo del sistema CREDIMEX. Consolida el levantamiento realizado con el cliente y define el alcance, actores, reglas de negocio, requerimientos funcionales y no funcionales de la primera versión.

La línea base puede recibir ajustes controlados durante el prototipado y las pruebas, pero contiene información suficiente para iniciar:

- Casos de uso.
- Diagramas de procesos.
- Prototipos de pantallas.
- Modelo de base de datos.
- Arquitectura.
- Planeación del desarrollo.

---

## 2. Definición del producto

CREDIMEX será una aplicación centralizada para Android orientada a una financiera de cobros diarios.

El sistema permitirá controlar:

- Usuarios y permisos.
- Clientes, referencias y contactos alternativos.
- Documentos y comprobantes.
- Ubicación GPS de domicilios.
- Créditos, intereses y comisiones.
- Renovaciones y reestructuraciones.
- Rutas y cobradores.
- Pagos diarios.
- Tickets térmicos.
- Transferencias.
- Cartera vencida.
- Clientes restringidos.
- Efectivo en posesión de cada cobrador.
- Entregas de dinero.
- Cortes de caja.
- Reportes, tablas, gráficas y auditoría.

### 2.1 Arquitectura conceptual

```text
Aplicación Android
        ↓
API central de CREDIMEX
        ↓
Base de datos central
        ↓
Almacenamiento de documentos
```

No se desarrollará una interfaz web en la versión 1; la arquitectura quedará preparada para incorporar posteriormente un panel web administrativo. El administrador, supervisor y cobrador utilizarán la misma aplicación Android, con funciones diferentes según su rol.

---

## 3. Alcance por versión

### 3.1 Versión 1

Incluye:

1. Usuarios, roles y permisos.
2. Alta de cobradores y supervisores.
3. Creación y administración de rutas.
4. Registro de clientes.
5. Referencias y contacto alternativo.
6. INE y comprobante de domicilio en fotografía.
7. Ubicación GPS del domicilio.
8. Catálogo de planes de crédito.
9. Solicitudes y autorizaciones.
10. Desembolso de créditos.
11. Comisión independiente del saldo.
12. Máximo de cinco créditos activos por cliente.
13. Cobros diarios.
14. Pagos parciales y anticipados.
15. Transferencias registradas por administrador o supervisor.
16. Tickets mediante impresora térmica Bluetooth.
17. Semáforo de atrasos.
18. Clientes restringidos.
19. Renovaciones.
20. Reestructuraciones.
21. Caja por cobrador.
22. Entregas parciales de efectivo.
23. Control de dinero conservado.
24. Cortes diarios.
25. Reportes y gráficas.
26. Auditoría.

### 3.2 Versión 2

Quedan reservados:

- API oficial de WhatsApp.
- Envío automático de tickets.
- Recordatorios y avisos de atraso.
- Portal o aplicación para clientes.
- Optimización automática de rutas.
- Modo de cobranza sin conexión.
- Integración bancaria automática.
- Firma electrónica.
- Geolocalización continua del cobrador.
- Panel web para administrador y supervisor.
- Aplicación para iPhone en una etapa posterior.

---

## 4. Plataforma y compatibilidad

- Plataforma: aplicación Android nativa.
- Lenguaje recomendado: Kotlin.
- Versión mínima propuesta: Android 10.
- Versión principal de referencia comercial: Android 14.
- Pruebas recomendadas: Android 10, 12, 13, 14 y versiones posteriores disponibles.
- Dispositivos: teléfonos Android y tabletas.
- Conectividad: requerida para confirmar operaciones financieras.
- Aplicación web: fuera de alcance.

La aplicación administrativa deberá adaptarse al uso horizontal en tabletas para facilitar tablas y gráficas.

---

## 5. Actores y jerarquía

### 5.1 Cobrador

Puede:

- Registrar clientes.
- Registrar referencias y contacto alternativo.
- Capturar documentos.
- Registrar ubicación GPS.
- Consultar su ruta y clientes.
- Solicitar créditos.
- Autorizar créditos dentro de su límite.
- Registrar desembolsos autorizados.
- Registrar pagos.
- Imprimir tickets.
- Registrar visitas sin pago.
- Consultar saldos, atrasos y efectivo esperado.

No puede:

- Consultar rutas ajenas.
- Modificar tasas o planes.
- Corregir o cancelar pagos.
- Mover clientes entre rutas.
- Crear usuarios o rutas.
- Autorizar importes superiores a su límite.

### 5.2 Supervisor

Hereda las funciones operativas del cobrador y además puede:

- Consultar todas las rutas.
- Crear y editar rutas.
- Mover clientes entre rutas.
- Sustituir cobradores.
- Autorizar créditos y renovaciones.
- Autorizar reestructuraciones.
- Registrar transferencias.
- Corregir o cancelar pagos mediante reverso.
- Recibir entregas de efectivo.
- Realizar y reabrir cortes.
- Administrar clientes restringidos.
- Consultar reportes generales.

### 5.3 Administrador

Hereda todos los privilegios del supervisor y cobrador, y además puede:

- Crear, modificar y bloquear usuarios.
- Crear cobradores y supervisores.
- Configurar roles y permisos.
- Crear y administrar rutas.
- Configurar planes, tasas, plazos y límites.
- Configurar días festivos.
- Aprobar y reabrir cortes.
- Consultar la auditoría completa.
- Configurar parámetros generales.
- Consultar todas las estadísticas.

### 5.4 Matriz de permisos

| Operación | Cobrador | Supervisor | Administrador |
|---|:---:|:---:|:---:|
| Registrar clientes | Sí | Sí | Sí |
| Capturar documentos y GPS | Sí | Sí | Sí |
| Consultar clientes de su ruta | Sí | Sí | Sí |
| Consultar todas las rutas | No | Sí | Sí |
| Crear usuarios | No | No | Sí |
| Crear cobradores y supervisores | No | No | Sí |
| Crear o editar rutas | No | Sí | Sí |
| Mover clientes entre rutas | No | Sí | Sí |
| Registrar pagos | Sí | Sí | Sí |
| Registrar transferencias | No | Sí | Sí |
| Corregir o cancelar pagos | No | Sí | Sí |
| Autorizar de $1,000 a $4,000 | Sí | Sí | Sí |
| Autorizar importes mayores | No | Sí | Sí |
| Configurar límite del cobrador | No | No | Sí |
| Autorizar renovación | Según límite | Sí | Sí |
| Autorizar reestructura | No | Sí | Sí |
| Recibir efectivo | No | Sí | Sí |
| Realizar o reabrir corte | No | Sí | Sí |
| Configurar intereses | No | No | Sí |
| Administrar restricciones | No | Sí | Sí |
| Consultar auditoría completa | No | Parcial | Sí |

---

## 6. Reglas de clientes

### RN-CLI-001 — Registro por los tres roles

Cobrador, supervisor y administrador podrán registrar clientes.

### RN-CLI-002 — Datos obligatorios

Cada cliente deberá tener:

- Nombre completo.
- Teléfono principal.
- Dirección.
- Ruta.
- Fotografía de la INE.
- Fotografía del comprobante de domicilio.
- Ubicación GPS.
- Nombre y teléfono de contacto alternativo.
- Relación con el contacto alternativo.
- Al menos una referencia.

### RN-CLI-003 — Comprobante de domicilio

Se aceptarán fotografías de documentos como luz, agua, internet, teléfono, predial u otro autorizado. Se guardará el tipo, fecha y usuario capturista.

### RN-CLI-004 — Referencias

Cada referencia tendrá nombre, teléfono, relación, observaciones y dirección cuando sea requerida.

### RN-CLI-005 — Historial

Se conservará el historial de teléfonos, domicilios, referencias, contactos alternativos, rutas, coordenadas GPS y documentos sustituidos.

### RN-CLI-006 — Duplicados

Antes del alta, el sistema buscará coincidencias por nombre, teléfono, identificación y dirección. El usuario podrá confirmar que se trata de otra persona.

### RN-CLI-007 — Máximo de créditos

Cada cliente podrá tener como máximo cinco créditos activos simultáneamente. El valor deberá ser configurable por el administrador.

### RN-CLI-008 — Créditos que no cuentan como activos

Los créditos liquidados, cancelados o castigados no contarán para el límite.

---

## 7. Clientes restringidos

### RN-RES-CLI-001 — Motivos

Un cliente podrá restringirse cuando:

- No liquide un crédito.
- Su crédito sea declarado incobrable.
- Presente incumplimiento grave.
- Proporcione documentación falsa.
- Sea bloqueado por administrador o supervisor.

### RN-RES-CLI-002 — Efectos

Un cliente restringido no podrá recibir nuevos créditos, renovaciones ni reestructuraciones con dinero adicional.

### RN-RES-CLI-003 — Revisión

La restricción tendrá fecha, motivo, créditos relacionados, responsable, fecha de revisión, estado y observaciones.

Estados:

- Activa.
- En revisión.
- Retirada.
- Permanente.

La fecha de revisión no retirará automáticamente la restricción. La decisión deberá ser realizada por supervisor o administrador y quedar auditada.

---

## 8. Montos y autorizaciones

### RN-MON-001 — Monto mínimo

El monto mínimo es $1,000.

### RN-MON-002 — Monto máximo

No existe un máximo comercial general. El administrador podrá configurar límites por rol, cliente, plan o política.

### RN-MON-003 — Incrementos

Normalmente se utilizarán incrementos de $1,000, pero el administrador podrá habilitar otros importes.

### RN-AUT-001 — Límite del cobrador

Inicialmente el cobrador puede autorizar créditos de $1,000 a $4,000.

### RN-AUT-002 — Configuración

El administrador podrá modificar el límite sin actualizar la aplicación.

### RN-AUT-003 — Escalamiento

Los importes superiores al límite del cobrador deberán ser autorizados por supervisor o administrador.

Toda autorización registrará usuario, fecha, monto, cliente, resultado y observaciones.

---

## 9. Planes e intereses

### 9.1 Planes iniciales

| Plazo | Interés |
|---:|---:|
| 20 días | 20% |
| 27 días | 21.5% |
| 40 días | 32% |
| 54 días | 40.4% |
| 68 días | 49.6% |

Todos los plazos están disponibles para todos los montos.

### RN-CAL-001 — Interés

```text
interés = monto × porcentaje del plazo
```

### RN-CAL-002 — Total

```text
total a pagar = monto + interés
```

### RN-CAL-003 — Cuota diaria

```text
cuota diaria = total a pagar / plazo
```

Cuando exista diferencia por redondeo, la última cuota se ajustará al saldo real.

### Ejemplo

Préstamo de $5,000 a 27 días:

```text
interés = 5,000 × 21.5% = 1,075
total = 5,000 + 1,075 = 6,075
cuota = 6,075 / 27 = 225 diarios
```

### RN-CAL-004 — Inmutabilidad de condiciones

Cada crédito conservará monto, plazo, porcentaje, interés, cuota, total, comisión, fecha y plan aplicado. Los cambios al catálogo no afectarán créditos anteriores.

---

## 10. Comisión

### RN-COM-001 — Importe

La comisión es de $20 por cada $100 prestados, equivalente al 20%.

```text
comisión = monto × 0.20
```

### RN-COM-002 — Cobro único e independiente

La comisión:

- Se cobra una sola vez por préstamo.
- No forma parte del saldo.
- No genera interés.
- No se reduce con pagos.
- Se registra como movimiento separado.

### RN-COM-003 — Modalidades de entrega

1. Se entrega el préstamo completo y la comisión se paga aparte.
2. Se descuenta la comisión del dinero entregado.
3. Se descuentan comisión y primer pago del dinero entregado.

### RN-COM-004 — Registro

La comisión guardará cliente, crédito, importe, modalidad, fecha, cobrador, ruta, medio de pago y descripción.

Si se cobra en efectivo, incrementará el efectivo esperado del cobrador.

---

## 11. Calendario y días festivos

### RN-CALEND-001 — Días programados

Se cobra de lunes a sábado. Los domingos no son días programados.

### RN-CALEND-002 — Días festivos

El administrador podrá configurar días festivos.

Durante un día festivo:

- No se genera cuota exigible.
- No se registra falta.
- No se incrementa el atraso.
- No se afecta el semáforo.
- No se suma ni resta al saldo.
- El siguiente día hábil conserva su cuota ordinaria.
- La fecha final se recorre para conservar el número de cuotas.

Los periodos de gracia quedan fuera de la versión 1.

---

## 12. Pagos

### RN-PAG-001 — Aplicación al saldo

```text
saldo nuevo = saldo anterior - importe pagado
```

Los pagos reducen el saldo total y no se dividen entre capital e interés.

### RN-PAG-002 — Tipos de pago

Se permiten pagos parciales, cuota completa, pago mayor a la cuota y liquidación anticipada.

### RN-PAG-003 — Liquidación anticipada

No existe descuento por liquidación anticipada.

### RN-PAG-004 — Correcciones

El cobrador no puede editar ni eliminar pagos.

Supervisor y administrador deberán corregir mediante:

1. Reverso del pago incorrecto.
2. Motivo obligatorio.
3. Registro de un pago nuevo.

El pago original permanecerá en auditoría.

---

## 13. Semáforo de atrasos

| Estado | Días programados sin pago |
|---|---:|
| Verde | 0 |
| Amarillo | 1 a 3 |
| Naranja | 4 a 7 |
| Rojo | 8 a 15 |
| Vencido | Más de 15 |

Solo se cuentan días programados. No cuentan domingos, días festivos ni fechas anteriores al primer pago.

Cuando exista un abono, el sistema recalculará el atraso conforme a las cuotas pendientes.

---

## 14. Transferencias

### RN-TRA-001 — Registro

Solo administrador y supervisor podrán registrar transferencias después de validar la recepción.

### RN-TRA-002 — Datos

La transferencia incluirá cliente, crédito, importe, fecha y hora, nombre de referencia, ruta o cobrador, cuenta receptora, referencia bancaria y usuario validador.

### RN-TRA-003 — Efectos

La transferencia:

- Reduce el saldo del crédito.
- No incrementa el efectivo físico del cobrador.
- Debe aparecer en la ruta para impedir un cobro duplicado.

---

## 15. GPS y evidencia

### RN-GPS-001

La ubicación GPS es obligatoria al registrar al cliente.

### RN-GPS-002

La evidencia ordinaria del pago será:

- Usuario.
- Fecha y hora.
- Folio.
- Ticket.
- Cliente y crédito.
- Dispositivo.
- Ubicación registrada del domicilio.

No se requiere fotografía por cada pago para reducir almacenamiento y consumo de datos.

### RN-GPS-003

Cambiar la ubicación requiere motivo, usuario, fecha, coordenadas anteriores y nuevas.

---

## 16. Impresión térmica y tickets

### 16.1 Impresora

Se utilizará una impresora térmica portátil Bluetooth de 58 mm, similar a la EasyTime propuesta.

Debe cumplir con:

- Papel térmico de 58 mm.
- Batería recargable.
- Bluetooth.
- Compatibilidad Android.
- Impresión de texto y logotipo.
- Códigos QR preferentemente.
- Protocolo ESC/POS o SDK documentado.
- Disponibilidad de consumibles.

Antes de comprar varias unidades se realizará una prueba de concepto con teléfonos Android 10, 12 y 14.

### RN-TIC-001 — Ticket

Cada pago genera un ticket con:

- Nombre de CREDIMEX.
- Folio único.
- Fecha y hora.
- Cliente.
- Importe.
- Saldo anterior.
- Saldo nuevo.
- Ruta.
- Cobrador.
- Medio de pago.
- Crédito.

### RN-TIC-002 — Confirmación

El ticket solo se imprimirá después de que el servidor confirme el pago.

### RN-TIC-003 — Reimpresión

Las reimpresiones mostrarán la leyenda “REIMPRESIÓN” y quedarán auditadas.

El envío automático por WhatsApp se reserva para la versión 2.

---

## 17. Rutas

### RN-RUT-001

Supervisor y administrador pueden crear, editar, activar, desactivar y asignar rutas.

### RN-RUT-002

Los nombres de las rutas son libres y configurables.

### RN-RUT-003

Cada ruta tendrá un cobrador titular.

### RN-RUT-004

Un crédito solo podrá estar habilitado para un cobrador a la vez.

### RN-RUT-005

Supervisor y administrador pueden mover clientes, sustituir cobradores, mezclar temporalmente rutas o asignar clientes específicos a otro cobrador.

Todo cambio conservará historial, fechas, motivo y usuario responsable.

---

## 18. Operación en línea

### RN-CON-001

Las operaciones financieras requieren conexión con el servidor:

- Pagos.
- Transferencias.
- Créditos.
- Desembolsos.
- Entregas de efectivo.
- Cortes.
- Reestructuraciones.

### RN-CON-002

Sin conexión solo se podrá visualizar información previamente consultada; no se confirmarán movimientos financieros.

### RN-CON-003

Antes de aceptar un pago, el servidor validará que:

- El crédito esté activo.
- El saldo sea vigente.
- El cliente esté asignado al cobrador.
- No exista otra operación en proceso.
- El corte no esté cerrado.

### RN-CON-004

Si el dispositivo o la conectividad fallan, supervisor o administrador podrán reasignar clientes o rutas.

### RN-CON-005

El cierre operativo se realiza aproximadamente a las 18:00 y los cortes entre las 19:00 y 20:00. Los horarios serán configurables.

---

## 19. Renovaciones

### RN-REN-001

El crédito que se desea renovar debe estar liquidado.

### RN-REN-002

El cliente puede conservar otros créditos activos sin superar el máximo de cinco.

### RN-REN-003

La renovación genera nuevo crédito, calendario, pagaré o contrato, comisión y autorización cuando corresponda.

### RN-REN-004

El nuevo crédito quedará relacionado con el anterior y conservará la ruta salvo cambio autorizado.

---

## 20. Reestructuraciones

### RN-REE-001

Solo supervisor y administrador pueden autorizar una reestructuración.

### RN-REE-002

La base de cálculo es el saldo pendiente:

```text
nuevo interés = saldo pendiente × tasa del nuevo plazo
nuevo total = saldo pendiente + nuevo interés
```

### RN-REE-003

La reestructuración puede extender plazo e incrementar interés, total, cuotas y fecha de terminación.

### RN-REE-004

Debe guardar saldo, plazo y cuota anteriores; tasa, interés, total y plazo nuevos; motivo, autorizador y fecha.

La operación anterior no se elimina.

---

## 21. Efectivo del cobrador

### RN-CAJ-001 — Fórmula

```text
efectivo esperado =
    saldo inicial
  + cobros en efectivo
  + comisiones en efectivo
  + dinero entregado por supervisor
  - préstamos desembolsados
  - gastos autorizados
  - entregas confirmadas al supervisor
```

Las transferencias no aumentan el efectivo físico.

### RN-CAJ-002 — Límite

El límite inicial es $20,000 por cobrador y será configurable.

Al aproximarse o superar el límite se genera una alerta.

### RN-CAJ-003 — Permanencia nocturna

El cobrador puede conservar efectivo por un máximo de tres noches, con autorización.

El saldo final autorizado pasa a ser saldo inicial del día siguiente. La tercera noche genera alerta prioritaria.

---

## 22. Entrega física de efectivo

Se utilizará doble confirmación.

### Paso 1 — Declaración

El cobrador registra cantidad, receptor, fecha, hora, observaciones y opcionalmente denominaciones. El estado es “Pendiente de recepción”.

### Paso 2 — Conteo

El supervisor captura cantidad declarada, cantidad recibida, diferencia y resultado.

### Paso 3 — Confirmación

Ambos confirman mediante sesión personal, PIN, código QR o folio.

### Paso 4 — Afectación

Después de confirmar:

- Disminuye el efectivo del cobrador.
- Aumenta la caja del receptor.
- Se genera comprobante.
- Se bloquea el movimiento.
- Se registra auditoría.

Una entrega no disminuirá el efectivo del cobrador hasta que sea confirmada por el receptor. Si existe diferencia se crea una incidencia.

---

## 23. Cortes

### RN-COR-001

Supervisor y administrador pueden realizar y reabrir cortes.

### RN-COR-002

Toda reapertura requiere motivo, usuario, fecha, hora y operaciones modificadas.

### RN-COR-003

El corte muestra:

- Saldo inicial.
- Cobranza en efectivo.
- Transferencias.
- Comisiones.
- Préstamos entregados.
- Gastos.
- Entregas parciales.
- Efectivo esperado.
- Efectivo contado.
- Diferencia.
- Dinero conservado.

```text
diferencia = efectivo contado - efectivo esperado
```

---

## 24. Conservación documental

Durante la vigencia del crédito, los documentos estarán disponibles para usuarios autorizados.

Al liquidarse el último crédito, pasarán a un archivo restringido. Después del periodo legal, contractual y administrativo aplicable podrán eliminarse de manera segura o anonimizarse.

El plazo definitivo deberá aprobarse con asesoría legal o contable.

---

## 25. Requerimientos funcionales consolidados

### RF-USU — Usuarios

- RF-USU-001: iniciar sesión.
- RF-USU-002: administrar roles y permisos.
- RF-USU-003: crear, bloquear y reactivar usuarios.
- RF-USU-004: crear cobradores y supervisores.
- RF-USU-005: vincular dispositivo y usuario.

### RF-CLI — Clientes

- RF-CLI-001: registrar clientes.
- RF-CLI-002: capturar INE y comprobante.
- RF-CLI-003: registrar GPS.
- RF-CLI-004: registrar referencias y contacto alternativo.
- RF-CLI-005: detectar duplicados.
- RF-CLI-006: consultar historial.
- RF-CLI-007: validar máximo de créditos.
- RF-CLI-008: administrar restricciones.

### RF-RUT — Rutas

- RF-RUT-001: crear y editar rutas.
- RF-RUT-002: asignar cobradores.
- RF-RUT-003: mover clientes.
- RF-RUT-004: realizar sustituciones temporales.
- RF-RUT-005: consultar avance por ruta.
- RF-RUT-006: mostrar ubicación de clientes.

### RF-CRE — Créditos

- RF-CRE-001: administrar planes.
- RF-CRE-002: calcular interés, total y cuota.
- RF-CRE-003: registrar solicitudes.
- RF-CRE-004: gestionar autorizaciones.
- RF-CRE-005: registrar desembolsos.
- RF-CRE-006: generar calendario.
- RF-CRE-007: conservar condiciones originales.
- RF-CRE-008: adjuntar contrato o pagaré.
- RF-CRE-009: consultar estados del crédito.

### RF-COM — Comisiones

- RF-COM-001: calcular comisión.
- RF-COM-002: registrar comisión separada.
- RF-COM-003: elegir modalidad.
- RF-COM-004: reflejar comisión en caja.

### RF-PAG — Pagos

- RF-PAG-001: registrar pagos parciales, completos y mayores.
- RF-PAG-002: actualizar saldo.
- RF-PAG-003: impedir eliminación física.
- RF-PAG-004: corregir mediante reverso.
- RF-PAG-005: recalcular atraso.
- RF-PAG-006: evitar duplicados.

### RF-TRA — Transferencias

- RF-TRA-001: administrar cuentas receptoras.
- RF-TRA-002: registrar transferencias verificadas.
- RF-TRA-003: informar al cobrador.
- RF-TRA-004: excluir transferencias del efectivo físico.

### RF-TIC — Tickets

- RF-TIC-001: imprimir ticket térmico.
- RF-TIC-002: guardar folio.
- RF-TIC-003: reimprimir con marca.
- RF-TIC-004: auditar reimpresiones.

### RF-REN — Renovaciones

- RF-REN-001: validar liquidación.
- RF-REN-002: crear nuevo crédito relacionado.
- RF-REN-003: generar nuevo calendario y documento.
- RF-REN-004: validar límite de créditos.

### RF-REE — Reestructuraciones

- RF-REE-001: calcular sobre saldo pendiente.
- RF-REE-002: almacenar condiciones anteriores y nuevas.
- RF-REE-003: requerir autorización.
- RF-REE-004: conservar historial.

### RF-CAJ — Caja y cortes

- RF-CAJ-001: registrar saldo inicial.
- RF-CAJ-002: calcular efectivo esperado.
- RF-CAJ-003: alertar por límite.
- RF-CAJ-004: controlar permanencia nocturna.
- RF-CAJ-005: registrar entregas parciales.
- RF-CAJ-006: doble confirmación.
- RF-CAJ-007: registrar diferencias.
- RF-CAJ-008: realizar corte.
- RF-CAJ-009: reabrir corte.
- RF-CAJ-010: mostrar tablas y gráficas.

### RF-AUD — Auditoría

- RF-AUD-001: registrar altas, cambios, reversos y ajustes.
- RF-AUD-002: guardar valor anterior y nuevo.
- RF-AUD-003: exigir motivo.
- RF-AUD-004: buscar por usuario, fecha, cliente y operación.

### RF-REP — Reportes

- RF-REP-001: cobranza diaria, semanal y mensual.
- RF-REP-002: cartera activa y vencida.
- RF-REP-003: atrasos por ruta y cliente.
- RF-REP-004: préstamos y renovaciones.
- RF-REP-005: productividad por cobrador.
- RF-REP-006: diferencias de caja.
- RF-REP-007: dinero en poder de cobradores.
- RF-REP-008: tablas y gráficas generales.

---

## 26. Requerimientos no funcionales

### Seguridad

- Comunicación cifrada.
- Contraseñas con hash seguro.
- Acceso basado en roles.
- Documentos restringidos.
- Sesiones vinculadas a dispositivos.
- PIN o reautenticación en operaciones sensibles.
- Sin eliminación física de movimientos.

### Integridad

- Folio único por operación.
- Validación de saldos en servidor.
- Transacciones atómicas.
- Prevención de duplicados.
- Reversos en lugar de eliminación.
- Conciliación entre pagos, créditos y caja.

### Rendimiento

- Registrar un pago en menos de tres segundos con conexión normal.
- Consultar una ruta en menos de cinco segundos.
- Imprimir inmediatamente después de la confirmación.
- Comprimir documentos antes de almacenarlos.
- Actualizar tableros sin recargar toda la aplicación.

### Disponibilidad

- Servicio disponible durante rutas y cortes.
- Respaldos automáticos.
- Monitoreo del servidor.
- Plan de recuperación.
- Mensajes claros ante falta de conexión.

### Usabilidad

- Registro de pago en pocas acciones.
- Botones grandes.
- Importes claramente visibles.
- Semáforo fácil de interpretar.
- Confirmaciones para operaciones sensibles.
- Interfaz adaptada por rol.
- Uso horizontal en tabletas para administración.

### Auditoría

Toda operación sensible conservará usuario, fecha, hora, dispositivo, acción, valor anterior, valor nuevo, motivo y entidad relacionada.

### Escalabilidad

- Agregar usuarios y rutas sin cambios de código.
- Configurar planes y límites desde la aplicación.
- Permitir crecimiento futuro a varias sucursales.
- Mantener parámetros modificables.

---

## 27. Estado de aprobación

| Componente | Estado |
|---|---|
| Objetivo y alcance | Aprobado |
| Usuarios y permisos | Aprobado |
| Clientes y referencias | Aprobado |
| Máximo de créditos | Aprobado |
| Clientes restringidos | Aprobado |
| Montos e intereses | Aprobado |
| Comisión | Aprobado |
| Calendario y festivos | Aprobado |
| Periodos de gracia | Eliminados |
| Pagos y semáforo | Aprobado |
| Transferencias | Aprobado |
| GPS y evidencia | Aprobado |
| Tickets | Aprobado, pendiente prueba de hardware |
| Rutas | Aprobado |
| Renovaciones | Aprobado |
| Reestructuraciones | Aprobado |
| Control de efectivo | Aprobado |
| Cortes | Aprobado |
| Plataforma Android | Aprobado |
| Retención documental | Revisión legal posterior |
| WhatsApp | Versión 2 |

---

## 28. Próxima fase

La siguiente fase desarrollará:

1. Catálogo de casos de uso.
2. Especificación detallada de casos de uso.
3. Diagramas de procesos actuales y futuros.
4. Prototipos de pantallas.
5. Modelo conceptual de datos.
6. Arquitectura y plan de desarrollo.

Los primeros procesos serán:

- Registrar cliente.
- Crear y autorizar crédito.
- Registrar pago e imprimir ticket.
- Entregar efectivo y realizar corte.
- Renovar o reestructurar crédito.

---

**Fin de la línea base v1.0**

---

# PARTE II — Casos de uso y diagramas de procesos

# 1. Objetivo de esta fase

El objetivo de esta fase es transformar las reglas de negocio y los requerimientos de CREDIMEX en procesos concretos que describan:

- Qué acciones realizará cada usuario.
- Qué información deberá capturar.
- Qué validaciones ejecutará el sistema.
- Qué decisiones necesitarán autorización.
- Qué resultados deberán producirse.
- Qué situaciones excepcionales deberán controlarse.
- Cómo se relacionan clientes, créditos, pagos, rutas, efectivo y cortes.

Los casos de uso servirán posteriormente como base para:

1. Diseñar las pantallas.
2. Crear el modelo de base de datos.
3. Definir los servicios de la API.
4. Dividir el desarrollo en módulos.
5. Preparar pruebas funcionales.
6. Validar el sistema con el cliente.

# 2. Actores del sistema

## 2.1 Cobrador

Es el usuario encargado de atender una ruta, registrar clientes, gestionar créditos dentro de sus límites, cobrar pagos e imprimir tickets.

Solo podrá consultar los clientes y créditos que tenga asignados.

## 2.2 Supervisor

Tiene todas las funciones operativas del cobrador y adicionalmente puede:

- Consultar todas las rutas.
- Autorizar créditos.
- Registrar transferencias.
- Corregir pagos.
- Administrar rutas.
- Mover clientes.
- Recibir efectivo.
- Realizar cortes.
- Autorizar renovaciones y reestructuraciones.
- Administrar clientes restringidos.

## 2.3 Administrador

Tiene todos los permisos del supervisor y cobrador, además de:

- Crear usuarios.
- Configurar permisos.
- Configurar planes de crédito.
- Configurar límites de autorización.
- Configurar días festivos.
- Consultar auditoría completa.
- Consultar estadísticas generales.
- Configurar parámetros del sistema.

## 2.4 Cliente

No inicia sesión en la versión 1, pero participa como actor externo en:

- Registro.
- Solicitud de crédito.
- Recepción del dinero.
- Pago.
- Renovación.
- Reestructuración.
- Recepción del ticket.

## 2.5 Impresora térmica

Dispositivo externo Bluetooth utilizado para imprimir comprobantes de pago.

## 2.6 Servicio de ubicación

Componente del teléfono que permite obtener las coordenadas GPS del domicilio del cliente.

# 3. Catálogo general de casos de uso

| ID | Caso de uso | Actor principal | Prioridad |
|---|---|---|---|
| UC-01 | Registrar cliente | Cobrador, supervisor o administrador | Alta |
| UC-02 | Actualizar información del cliente | Cobrador, supervisor o administrador | Alta |
| UC-03 | Crear y autorizar crédito | Cobrador, supervisor o administrador | Alta |
| UC-04 | Desembolsar crédito y registrar comisión | Cobrador, supervisor o administrador | Alta |
| UC-05 | Registrar pago e imprimir ticket | Cobrador, supervisor o administrador | Alta |
| UC-06 | Registrar visita sin pago | Cobrador | Alta |
| UC-07 | Registrar pago por transferencia | Supervisor o administrador | Alta |
| UC-08 | Corregir o cancelar pago | Supervisor o administrador | Alta |
| UC-09 | Entregar efectivo al supervisor | Cobrador y supervisor | Alta |
| UC-10 | Registrar gasto autorizado | Supervisor o administrador | Alta |
| UC-11 | Realizar corte diario | Supervisor o administrador | Alta |
| UC-12 | Reabrir corte | Supervisor o administrador | Alta |
| UC-13 | Renovar crédito | Cobrador, supervisor o administrador | Alta |
| UC-14 | Reestructurar crédito | Supervisor o administrador | Alta |
| UC-15 | Crear y administrar rutas | Supervisor o administrador | Alta |
| UC-16 | Reasignar cliente o cubrir una ruta | Supervisor o administrador | Alta |
| UC-17 | Restringir o rehabilitar cliente | Supervisor o administrador | Alta |
| UC-18 | Administrar usuarios | Administrador | Alta |
| UC-19 | Configurar planes, tasas y límites | Administrador | Alta |
| UC-20 | Configurar días festivos | Administrador | Media |
| UC-21 | Consultar tablero operativo | Supervisor o administrador | Alta |
| UC-22 | Generar reportes | Supervisor o administrador | Alta |
| UC-23 | Consultar auditoría | Supervisor o administrador | Alta |

# 4. Diagrama general del proceso futuro

```text
INICIO
  │
  ▼
Usuario inicia sesión
  │
  ▼
Sistema identifica rol y permisos
  │
  ├──────────── Cobrador ────────────┐
  │                                  │
  ├──────────── Supervisor ──────────┤
  │                                  │
  └──────────── Administrador ───────┘
                                     │
                                     ▼
                         Registro o búsqueda del cliente
                                     │
                                     ▼
                     Validación de duplicados y restricciones
                                     │
                         ┌───────────┴───────────┐
                         │                       │
                    Cliente válido       Cliente restringido
                         │                       │
                         ▼                       ▼
                Solicitud de crédito     Bloqueo de operación
                         │
                         ▼
              Selección de monto y plazo
                         │
                         ▼
                Cálculo de interés,
              cuota, total y comisión
                         │
                         ▼
              ¿Requiere autorización?
                    ┌────┴────┐
                    │         │
                   No        Sí
                    │         │
                    │    Supervisor o
                    │    administrador
                    │         │
                    └────┬────┘
                         ▼
                 Crédito autorizado
                         │
                         ▼
              Desembolso y comisión
                         │
                         ▼
             Generación del calendario
                         │
                         ▼
              Asignación a una ruta
                         │
                         ▼
                 Cobranza diaria
                         │
                ┌────────┴────────┐
                │                 │
             Hay pago         No hay pago
                │                 │
                ▼                 ▼
          Registrar pago    Registrar motivo
                │           y promesa de pago
                ▼
          Actualizar saldo
                │
                ▼
          Imprimir ticket
                │
                ▼
       Actualizar efectivo del cobrador
                │
                ▼
       Entregas parciales de efectivo
                │
                ▼
             Corte diario
                │
                ▼
        Reportes y auditoría
                │
                ▼
               FIN
```

# 5. UC-01 — Registrar cliente

## 5.1 Objetivo

Registrar en CREDIMEX la información personal, documental, geográfica y de contacto necesaria para identificar a un cliente y permitirle posteriormente solicitar créditos.

## 5.2 Actores

- Cobrador.
- Supervisor.
- Administrador.
- Servicio GPS.

## 5.3 Disparador

Una persona solicita por primera vez un crédito o necesita ser incorporada como cliente.

## 5.4 Precondiciones

- El usuario inició sesión.
- El usuario tiene permiso para registrar clientes.
- Existe conexión con el servidor.
- La ruta donde se registrará el cliente se encuentra activa.
- El teléfono tiene permiso para utilizar la cámara y la ubicación.

## 5.5 Datos obligatorios

### Datos personales

- Nombre completo.
- Teléfono principal.
- Dirección.
- Ruta.

### Contacto alternativo

- Nombre.
- Teléfono.
- Relación con el cliente.

### Referencia

- Nombre.
- Teléfono.
- Relación.
- Observaciones, cuando corresponda.

### Documentos

- Fotografía de la INE.
- Fotografía del comprobante de domicilio.
- Tipo de comprobante.

### Ubicación

- Latitud.
- Longitud.
- Fecha y hora de captura.

## 5.6 Flujo principal

1. El usuario selecciona **Clientes**.
2. Selecciona **Registrar cliente**.
3. El sistema solicita los datos personales.
4. El usuario captura nombre, teléfono y dirección.
5. El usuario selecciona la ruta.
6. El sistema busca coincidencias por nombre, teléfono, dirección e identificación.
7. Si no existe una coincidencia crítica, permite continuar.
8. El usuario registra el contacto alternativo.
9. El usuario registra al menos una referencia.
10. El usuario fotografía la INE.
11. El usuario fotografía el comprobante de domicilio.
12. El usuario selecciona el tipo de comprobante.
13. El usuario solicita capturar la ubicación GPS.
14. El sistema obtiene latitud y longitud.
15. El sistema muestra un resumen.
16. El usuario confirma el registro.
17. El sistema crea un identificador único para el cliente.
18. El sistema registra usuario, dispositivo, fecha y hora.
19. El cliente queda activo y disponible para solicitar créditos.

## 5.7 Flujos alternativos

### A1. Posible cliente duplicado

1. El sistema encuentra una coincidencia.
2. Muestra los clientes similares.
3. El usuario revisa la información.
4. Puede seleccionar al cliente existente, cancelar el registro o confirmar que se trata de otra persona.
5. La confirmación de una persona diferente deberá quedar registrada.

### A2. Ubicación incorrecta

1. El sistema obtiene una ubicación.
2. El usuario detecta que no corresponde al domicilio.
3. Solicita una nueva captura.
4. El sistema reemplaza la ubicación antes de guardar.

### A3. Se requieren varias referencias

El usuario podrá agregar más referencias antes de guardar al cliente.

## 5.8 Excepciones

### E1. Sin conexión

El sistema no permitirá finalizar el registro y mostrará: “No se puede registrar el cliente porque no existe conexión con el servidor”.

### E2. Fotografía ilegible

El usuario deberá volver a capturar el documento.

### E3. GPS desactivado

El sistema solicitará activar la ubicación.

### E4. Ruta inactiva

No se permitirá seleccionar una ruta inactiva.

## 5.9 Postcondiciones

- El cliente queda registrado.
- Tiene una ruta asignada.
- Sus documentos quedan relacionados.
- Su ubicación queda almacenada.
- Se genera una entrada de auditoría.

## 5.10 Reglas relacionadas

- RN-CLI-001 a RN-CLI-008.
- RN-GPS-001 a RN-GPS-003.
- Máximo de cinco créditos activos.
- Detección de clientes duplicados.

## 5.11 Criterios de aceptación

- No se puede guardar sin INE, comprobante, GPS, contacto y referencia.
- El sistema debe advertir posibles duplicados.
- El cliente debe quedar ligado a una ruta.
- El cobrador solo podrá verlo si pertenece a su ruta.
- La captura deberá indicar quién realizó el registro.

## 5.12 Diagrama del proceso

```text
INICIO
  │
  ▼
Seleccionar “Registrar cliente”
  │
  ▼
Capturar datos personales
  │
  ▼
Buscar coincidencias
  │
  ├── ¿Existe cliente similar? ── Sí ──► Mostrar coincidencias
  │                                      │
  │                                      ├── Usar existente
  │                                      ├── Cancelar
  │                                      └── Confirmar persona diferente
  │
  ▼
Registrar contacto alternativo
  │
  ▼
Registrar referencia
  │
  ▼
Fotografiar INE
  │
  ▼
Fotografiar comprobante
  │
  ▼
Capturar GPS
  │
  ▼
Validar información
  │
  ├── Información incompleta ──► Solicitar corrección
  │
  ▼
Guardar cliente
  │
  ▼
Asignar identificador y ruta
  │
  ▼
Registrar auditoría
  │
  ▼
FIN
```

# 6. UC-02 — Actualizar información del cliente

## Objetivo

Modificar datos de contacto, domicilio, referencias, documentos o ubicación, conservando el historial anterior.

## Actores

- Cobrador.
- Supervisor.
- Administrador.

## Precondiciones

- El cliente existe.
- El usuario tiene acceso al cliente.
- Existe conexión.
- El cliente no está eliminado.

## Flujo principal

1. El usuario busca al cliente.
2. El sistema muestra su expediente.
3. El usuario selecciona **Editar**.
4. Modifica uno o más datos.
5. El sistema identifica los campos modificados.
6. Cuando se modifica domicilio o GPS, solicita un motivo.
7. Cuando se reemplaza un documento, conserva el documento anterior.
8. El usuario confirma.
9. El sistema guarda valor anterior, valor nuevo, usuario, fecha y motivo.
10. El expediente actualizado queda disponible.

## Excepciones

- El cobrador no podrá modificar un cliente de otra ruta.
- No se permitirá eliminar permanentemente documentos anteriores.
- No se permitirá mover al cliente de ruta desde esta pantalla; deberá utilizarse UC-16.

## Resultado

El cliente conserva su información vigente y el historial completo de modificaciones.

# 7. UC-03 — Crear y autorizar crédito

## 7.1 Objetivo

Registrar una solicitud, calcular sus condiciones financieras y obtener la autorización correspondiente.

## 7.2 Actores

- Cobrador.
- Supervisor.
- Administrador.
- Cliente.

## 7.3 Precondiciones

- El cliente está registrado.
- El cliente no está restringido.
- El cliente tiene menos de cinco créditos activos.
- El cliente tiene una ruta activa.
- El usuario tiene conexión.

## 7.4 Flujo principal

1. El usuario abre el expediente del cliente.
2. Selecciona **Nuevo crédito**.
3. El sistema muestra créditos activos, saldos, atrasos, historial y estado de restricción.
4. El usuario captura el monto.
5. El sistema valida que sea igual o mayor a $1,000.
6. El usuario selecciona un plazo de 20, 27, 40, 54 o 68 días.
7. El sistema calcula porcentaje, interés, total, cuota y comisión.
8. El sistema muestra la fecha de inicio del pago.
9. El usuario confirma la solicitud.
10. El sistema compara el monto con el límite del usuario.
11. Si está dentro de su límite, el usuario puede autorizarlo.
12. Si supera el límite, la solicitud se envía al supervisor o administrador.
13. El autorizador consulta la información.
14. El autorizador aprueba o rechaza.
15. El sistema registra la decisión.
16. Si se aprueba, el crédito queda en estado **Autorizado pendiente de desembolso**.

## 7.5 Fórmulas

```text
interés = monto × tasa del plazo

total a pagar = monto + interés

cuota diaria = total a pagar / número de días

comisión = monto × 20%
```

## 7.6 Flujos alternativos

### A1. Crédito dentro del límite del cobrador

El cobrador podrá autorizar inicialmente importes entre $1,000 y $4,000.

### A2. Crédito superior al límite

La solicitud queda pendiente hasta que supervisor o administrador la revise.

### A3. Solicitud rechazada

El autorizador deberá registrar el motivo y la solicitud quedará con estado **Rechazada**.

### A4. Solicitud devuelta

El autorizador podrá devolverla para corregir monto, plazo o documentación.

## 7.7 Excepciones

- Cliente restringido.
- Cliente con cinco créditos activos.
- Monto menor a $1,000.
- Plan inactivo.
- Falta de documentos.
- Falta de conexión.

## 7.8 Postcondiciones

- Solicitud autorizada, rechazada o devuelta.
- Condiciones financieras guardadas.
- Auditoría de la autorización.
- No se entrega dinero todavía.

## 7.9 Criterios de aceptación

- El cálculo deberá coincidir con el plan.
- El cobrador no podrá autorizar por encima de su límite.
- No se permitirá un sexto crédito activo.
- No se permitirá crédito a un cliente restringido.
- Cambiar el catálogo después no deberá modificar esta solicitud.

## 7.10 Diagrama

```text
INICIO
  │
  ▼
Seleccionar cliente
  │
  ▼
Validar restricción y créditos activos
  │
  ├── Restringido ───────────► Bloquear solicitud
  │
  ├── Tiene 5 activos ───────► Bloquear solicitud
  │
  ▼
Capturar monto
  │
  ▼
Seleccionar plazo
  │
  ▼
Calcular interés, cuota,
total y comisión
  │
  ▼
Mostrar resumen
  │
  ▼
¿Monto dentro del límite?
  │
  ├── Sí ──► Autorizar por usuario
  │
  └── No ──► Enviar a supervisor/administrador
                   │
                   ▼
             Revisar solicitud
                   │
            ┌──────┴──────┐
            │             │
          Aprobar      Rechazar
            │             │
            ▼             ▼
     Pendiente de       Registrar
      desembolso         motivo
            │
            ▼
           FIN
```

# 8. UC-04 — Desembolsar crédito y registrar comisión

## 8.1 Objetivo

Registrar la entrega del préstamo al cliente, el cobro de la comisión y el inicio del calendario de pagos.

## 8.2 Actores

- Cobrador.
- Supervisor.
- Administrador.
- Cliente.

## 8.3 Precondiciones

- El crédito está autorizado.
- El crédito no ha sido desembolsado.
- El usuario tiene suficiente efectivo registrado.
- Existe conexión.
- El corte está abierto.

## 8.4 Flujo principal

1. El usuario selecciona el crédito autorizado.
2. Selecciona **Desembolsar**.
3. El sistema muestra monto autorizado, comisión, cuota diaria y total del crédito.
4. El usuario selecciona una modalidad: entrega completa y comisión aparte; descuento de comisión; o descuento de comisión y primer pago.
5. El sistema calcula el efectivo que recibirá el cliente.
6. El usuario confirma la entrega.
7. El sistema registra monto, comisión, modalidad, efectivo entregado, cobrador, ruta y fecha.
8. El sistema descuenta el desembolso del efectivo del cobrador.
9. Si la comisión se recibió en efectivo, la suma al efectivo del cobrador.
10. Si se descontó el primer pago, registra el pago, reduce el saldo y genera su folio.
11. El sistema genera el calendario.
12. El primer pago ordinario inicia al día siguiente.
13. El crédito cambia a estado **Activo**.
14. Se genera el contrato o pagaré relacionado.

## 8.5 Ejemplo

```text
Monto del préstamo:             $5,000
Comisión:                       $1,000
Interés según plazo:            variable
Saldo del crédito:              monto + interés
```

Modalidad con descuento de comisión:

```text
Efectivo recibido por cliente:  $4,000
Saldo del crédito:              no cambia
Comisión pendiente:             $0
```

## 8.6 Excepciones

- Efectivo insuficiente.
- Crédito ya desembolsado.
- Crédito cancelado.
- Corte cerrado.
- Falta de conexión.
- Monto entregado diferente al calculado.

## 8.7 Criterios de aceptación

- La comisión no aumenta el saldo.
- El desembolso disminuye el efectivo del cobrador.
- El crédito se activa una sola vez.
- El calendario conserva los días definidos.
- El sistema deberá impedir un segundo desembolso.

# 9. UC-05 — Registrar pago e imprimir ticket

## 9.1 Objetivo

Aplicar un pago al crédito, actualizar su saldo, recalcular el atraso e imprimir un comprobante.

## 9.2 Actores

- Cobrador.
- Supervisor.
- Administrador.
- Cliente.
- Impresora térmica.

## 9.3 Precondiciones

- El crédito está activo.
- El cliente está asignado al cobrador.
- El corte está abierto.
- Existe conexión.
- La impresora se encuentra configurada para imprimir, aunque el pago podrá confirmarse si la impresora falla.

## 9.4 Flujo principal

1. El usuario abre su ruta.
2. Selecciona al cliente.
3. El sistema muestra sus créditos activos.
4. El usuario selecciona un crédito.
5. El sistema muestra saldo, cuota, atraso, semáforo y últimos pagos.
6. El usuario selecciona **Registrar pago**.
7. Captura el importe.
8. Selecciona medio de pago.
9. El sistema valida que el importe sea mayor que cero.
10. El servidor valida crédito activo, saldo vigente, asignación de ruta, corte abierto y ausencia de otro pago en proceso.
11. El usuario confirma.
12. El sistema genera un folio único.
13. El sistema actualiza el saldo.
14. El sistema recalcula el atraso.
15. Si fue efectivo, aumenta el efectivo esperado del cobrador.
16. El sistema genera el ticket.
17. El usuario envía el ticket a la impresora.
18. El sistema registra si la impresión fue exitosa.
19. El pago aparece inmediatamente en la ruta.

## 9.5 Flujos alternativos

### A1. Pago menor a la cuota

Se aplica al saldo y el importe faltante permanece pendiente.

### A2. Pago mayor a la cuota

Se aplica todo al saldo total.

### A3. Liquidación

Si el pago cubre todo el saldo, el saldo queda en cero, el crédito cambia a **Liquidado**, deja de aparecer como activo y puede habilitarse una renovación.

### A4. Impresora no disponible

1. El pago queda registrado.
2. El sistema informa que no pudo imprimir.
3. Permite reintentar.
4. La reimpresión posterior llevará la leyenda correspondiente.

## 9.6 Excepciones

- Sin conexión: no se registra el pago.
- Crédito reasignado: se bloquea la operación.
- Saldo modificado: se actualiza la pantalla y se solicita capturar nuevamente.
- Corte cerrado: no se aplica hasta reabrir o iniciar una nueva jornada.

## 9.7 Postcondiciones

- Pago registrado.
- Saldo actualizado.
- Semáforo recalculado.
- Efectivo actualizado.
- Ticket disponible.
- Auditoría creada.

## 9.8 Criterios de aceptación

- El pago no puede registrarse dos veces.
- El ticket debe mostrar el importe real.
- El saldo anterior y nuevo deben coincidir.
- La impresión solo debe ejecutarse después de la confirmación del servidor.
- Un fallo de impresión no debe cancelar el pago.

## 9.9 Diagrama

```text
INICIO
  │
  ▼
Seleccionar cliente y crédito
  │
  ▼
Mostrar saldo, cuota y atraso
  │
  ▼
Capturar importe
  │
  ▼
Validar conexión y asignación
  │
  ├── Sin conexión ──────────► Bloquear pago
  │
  ├── Cliente reasignado ────► Bloquear pago
  │
  ▼
Confirmar pago
  │
  ▼
Generar folio único
  │
  ▼
Actualizar saldo
  │
  ▼
Recalcular semáforo
  │
  ▼
Actualizar efectivo
  │
  ▼
Generar ticket
  │
  ▼
¿Impresora disponible?
  │
  ├── Sí ──► Imprimir ticket
  │
  └── No ──► Guardar pendiente de impresión
  │
  ▼
FIN
```

# 10. UC-06 — Registrar visita sin pago

## Objetivo

Dejar evidencia de que el cliente fue visitado, aunque no haya realizado un abono.

## Actor

Cobrador.

## Precondiciones

- El cliente pertenece a su ruta.
- El crédito está activo.
- Existe conexión.

## Flujo principal

1. El cobrador selecciona al cliente.
2. Selecciona **Sin pago**.
3. Selecciona un motivo: cliente ausente, sin dinero, promesa de pago, domicilio cerrado, domicilio incorrecto, no localizado, se negó a pagar u otro.
4. Captura observaciones.
5. Si existe promesa, registra fecha prometida y monto prometido.
6. Confirma la visita.
7. El sistema registra fecha, hora, cobrador y motivo.
8. Si correspondía una cuota, el día contará para el semáforo.

## Excepciones

- En día festivo no se generará falta.
- Un motivo no elimina automáticamente la cuota.
- Un cliente no localizado podrá enviarse a revisión de ruta.

# 11. UC-07 — Registrar pago por transferencia

## Objetivo

Aplicar al crédito un pago recibido en una cuenta de CREDIMEX sin aumentar el efectivo físico del cobrador.

## Actores

- Supervisor.
- Administrador.

## Precondiciones

- El movimiento fue verificado en la cuenta.
- El crédito está activo.
- Existe conexión.
- El usuario tiene permiso.

## Flujo principal

1. El usuario selecciona **Transferencias**.
2. Selecciona la cuenta receptora.
3. Busca al cliente.
4. Selecciona el crédito.
5. Captura monto, fecha, hora, nombre o referencia utilizada, ruta o cobrador relacionado y referencia bancaria.
6. Confirma que el movimiento fue validado.
7. El sistema aplica el pago.
8. Actualiza el saldo.
9. Recalcula el atraso.
10. Informa al cobrador de la ruta.
11. No aumenta el efectivo físico del cobrador.
12. Genera folio y auditoría.

## Excepciones

- Referencia ya utilizada.
- Transferencia duplicada.
- Crédito liquidado.
- Importe mayor al saldo.
- Transferencia no confirmada.

# 12. UC-08 — Corregir o cancelar pago

## Objetivo

Corregir una operación incorrecta sin eliminar el historial financiero.

## Actores

- Supervisor.
- Administrador.

## Precondiciones

- El pago existe.
- El usuario tiene permisos.
- El pago no fue previamente revertido.
- El corte puede modificarse o reabrirse.

## Flujo principal

1. El usuario localiza el pago.
2. Selecciona **Corregir pago**.
3. Captura el motivo.
4. El sistema muestra las afectaciones sobre saldo, efectivo, corte y semáforo.
5. El usuario confirma el reverso.
6. El sistema crea una operación inversa.
7. El pago original queda marcado como **Revertido**.
8. El sistema recalcula saldo y caja.
9. Si corresponde, el usuario registra el pago correcto.
10. Se guarda la relación entre pago original, reverso y nuevo pago.

## Regla principal

Ningún pago financiero se elimina físicamente.

# 13. UC-09 — Entregar efectivo al supervisor

## 13.1 Objetivo

Registrar y confirmar la entrega física de dinero de un cobrador a un supervisor.

## 13.2 Actores

- Cobrador.
- Supervisor.
- Administrador, cuando recibe directamente.

## 13.3 Precondiciones

- Ambos usuarios tienen sesión activa.
- Existe conexión.
- El cobrador tiene efectivo registrado.
- El corte no está cerrado.

## 13.4 Flujo principal

### Etapa 1: declaración

1. El cobrador selecciona **Entregar efectivo**.
2. El sistema muestra su efectivo esperado.
3. Captura el importe que entregará.
4. Selecciona al receptor.
5. Opcionalmente registra denominaciones.
6. Confirma.
7. La entrega queda **Pendiente de recepción**.

### Etapa 2: recepción

8. El supervisor abre la entrega.
9. Cuenta el efectivo.
10. Captura el importe recibido.
11. El sistema calcula la diferencia.

### Etapa 3: confirmación

12. Si los importes coinciden, el supervisor confirma.
13. El cobrador confirma mediante PIN, código o sesión.
14. El sistema reduce el efectivo del cobrador, aumenta la caja del receptor, genera folio, cierra el movimiento y registra auditoría.

## 13.5 Flujo alternativo: diferencia

1. El importe recibido no coincide.
2. El sistema genera una incidencia.
3. El supervisor captura una observación.
4. El cobrador registra su explicación.
5. La entrega queda pendiente de resolución.
6. No se ajustan saldos automáticamente hasta que exista una decisión.

## 13.6 Criterios de aceptación

- Una sola persona no puede cerrar toda la operación.
- La entrega no debe descontarse antes de ser confirmada.
- Debe conservarse el importe declarado y recibido.
- Las diferencias deben quedar rastreables.

## 13.7 Diagrama

```text
INICIO
  │
  ▼
Cobrador consulta efectivo esperado
  │
  ▼
Captura importe a entregar
  │
  ▼
Selecciona receptor
  │
  ▼
Entrega pendiente de recepción
  │
  ▼
Supervisor cuenta dinero
  │
  ▼
Captura importe recibido
  │
  ▼
¿Coinciden los importes?
  │
  ├── Sí
  │    │
  │    ▼
  │  Doble confirmación
  │    │
  │    ▼
  │  Transferir saldo
  │    │
  │    ▼
  │  Generar comprobante
  │
  └── No
       │
       ▼
   Crear incidencia
       │
       ▼
   Registrar explicaciones
       │
       ▼
   Resolver diferencia
       │
       ▼
      FIN
```

# 14. UC-10 — Registrar gasto autorizado

## Objetivo

Registrar un gasto de ruta que será descontado del efectivo esperado del cobrador.

## Actores

- Supervisor.
- Administrador.

## Flujo principal

1. El cobrador comunica el gasto.
2. Supervisor o administrador selecciona la ruta y cobrador.
3. Captura importe, concepto, fecha, motivo y observaciones.
4. Confirma la autorización.
5. El sistema descuenta el importe del efectivo esperado.
6. El gasto aparece en el corte.
7. Se genera auditoría.

## Reglas

- El cobrador no puede autorizar sus propios gastos.
- Todo gasto debe tener concepto y autorizador.
- Un gasto posterior al corte requiere reapertura o ajuste.

# 15. UC-11 — Realizar corte diario

## 15.1 Objetivo

Conciliar todos los movimientos del cobrador durante la jornada y determinar el efectivo que debe entregar o conservar.

## 15.2 Actores

- Supervisor.
- Administrador.
- Cobrador como participante.

## 15.3 Precondiciones

- La jornada terminó.
- No existen pagos en proceso.
- Las entregas pendientes fueron confirmadas o identificadas.
- Existe conexión.
- El cobrador no tiene otro corte abierto para la fecha.

## 15.4 Información del corte

- Saldo inicial.
- Cobros en efectivo.
- Transferencias.
- Comisiones.
- Dinero recibido del supervisor.
- Préstamos desembolsados.
- Gastos autorizados.
- Entregas parciales.
- Efectivo esperado.
- Efectivo contado.
- Diferencia.
- Dinero conservado.
- Número de noches de permanencia.

## 15.5 Flujo principal

1. El supervisor selecciona al cobrador.
2. El sistema calcula el efectivo esperado.
3. El cobrador declara el efectivo que conserva.
4. El supervisor captura el efectivo contado.
5. El sistema calcula la diferencia.
6. Si no existe diferencia, permite continuar.
7. Se define dinero entregado y dinero conservado.
8. Si conserva dinero, valida que no supere tres noches y que no exceda el límite sin autorización.
9. Supervisor o administrador confirma el corte.
10. El sistema cierra la jornada, bloquea modificaciones ordinarias, genera el reporte, define el saldo inicial siguiente y registra auditoría.

## 15.6 Flujos alternativos

### A1. Existe diferencia

1. Se crea una incidencia.
2. Se registra explicación.
3. Se determina el responsable.
4. El corte puede quedar pendiente, cerrado con diferencia o en revisión.

### A2. Excede $20,000

Se genera una alerta y se solicita entrega de efectivo.

### A3. Tercera noche

El sistema genera una alerta prioritaria y requiere una decisión del supervisor o administrador.

## 15.7 Diagrama

```text
INICIO DEL CORTE
  │
  ▼
Seleccionar cobrador y fecha
  │
  ▼
Calcular efectivo esperado
  │
  ▼
Capturar efectivo contado
  │
  ▼
Calcular diferencia
  │
  ├── Diferencia = 0
  │        │
  │        ▼
  │   Definir entrega
  │   y dinero conservado
  │
  └── Diferencia ≠ 0
           │
           ▼
       Crear incidencia
           │
           ▼
       Registrar explicación
           │
           ▼
       Resolver o dejar
       pendiente de revisión
           │
           ▼
Validar límite y noches
  │
  ▼
Confirmar corte
  │
  ▼
Cerrar jornada
  │
  ▼
Generar reporte
  │
  ▼
Definir saldo inicial siguiente
  │
  ▼
FIN
```

# 16. UC-12 — Reabrir corte

## Objetivo

Permitir modificar una jornada cerrada manteniendo la trazabilidad de la reapertura.

## Actores

- Supervisor.
- Administrador.

## Flujo principal

1. El usuario selecciona un corte cerrado.
2. Selecciona **Reabrir corte**.
3. Captura un motivo.
4. El sistema registra usuario, fecha y hora.
5. El corte pasa a estado **Reabierto**.
6. Se permiten reversos o ajustes autorizados.
7. El usuario vuelve a realizar el corte.
8. El sistema conserva las versiones anteriores.

## Regla

Reabrir un corte no elimina la versión inicial.

# 17. UC-13 — Renovar crédito

## Objetivo

Crear un nuevo préstamo a partir de un crédito liquidado, conservando la relación histórica.

## Actores

- Cobrador.
- Supervisor.
- Administrador.

## Precondiciones

- El crédito anterior está liquidado.
- El cliente no está restringido.
- No supera cinco créditos activos.
- Existe conexión.

## Flujo principal

1. El usuario abre el crédito liquidado.
2. Selecciona **Renovar**.
3. El sistema muestra el historial.
4. El usuario captura nuevo monto y plazo.
5. El sistema calcula interés, total, cuota y comisión.
6. Se aplica el flujo de autorización.
7. Cuando se aprueba, se crea un nuevo crédito.
8. Se relaciona con el crédito anterior.
9. Se genera nuevo documento.
10. Se realiza el desembolso mediante UC-04.
11. Se conserva la ruta actual.

## Excepciones

- Crédito anterior con saldo.
- Cliente restringido.
- Cinco créditos activos.
- Monto superior sin autorización.

# 18. UC-14 — Reestructurar crédito

## 18.1 Objetivo

Modificar las condiciones de un crédito utilizando el saldo pendiente como base para un nuevo cálculo.

## 18.2 Actores

- Supervisor.
- Administrador.

## 18.3 Precondiciones

- El crédito tiene saldo.
- El usuario tiene permisos.
- Existe conexión.
- Se registró un motivo.

## 18.4 Flujo principal

1. El usuario selecciona el crédito.
2. Selecciona **Reestructurar**.
3. El sistema muestra saldo actual, plazo original, cuota original, pagos realizados y atraso.
4. El usuario selecciona el nuevo plazo.
5. El sistema calcula nuevo interés, nuevo total y nueva cuota.
6. El sistema compara condiciones anteriores y nuevas.
7. El usuario captura el motivo.
8. Confirma la operación.
9. El sistema conserva las condiciones anteriores.
10. Crea el nuevo calendario.
11. Actualiza el saldo.
12. Registra autorizador, fecha y auditoría.

## 18.5 Reglas

- La reestructura no debe modificar los pagos anteriores.
- No se elimina el crédito original.
- No se entrega dinero adicional, salvo política futura.
- Un cliente restringido podrá reestructurar solo para recuperar el saldo, no para recibir dinero nuevo.

## 18.6 Diagrama

```text
INICIO
  │
  ▼
Seleccionar crédito con saldo
  │
  ▼
Mostrar condiciones actuales
  │
  ▼
Seleccionar nuevo plazo
  │
  ▼
Calcular nuevo interés,
total y cuota
  │
  ▼
Comparar antes y después
  │
  ▼
Capturar motivo
  │
  ▼
Confirmar autorización
  │
  ▼
Conservar datos anteriores
  │
  ▼
Crear nuevo calendario
  │
  ▼
Actualizar saldo
  │
  ▼
Registrar auditoría
  │
  ▼
FIN
```

# 19. UC-15 — Crear y administrar rutas

## Objetivo

Permitir configurar las zonas de cobranza y asignar un cobrador titular.

## Actores

- Supervisor.
- Administrador.

## Flujo principal

1. El usuario selecciona **Rutas**.
2. Puede crear una ruta, editar su nombre, activarla, desactivarla o asignar cobrador titular.
3. El sistema valida que el cobrador esté activo.
4. El usuario confirma.
5. La ruta queda disponible.
6. Se registra el cambio en auditoría.

## Reglas

- El nombre será libre.
- Una ruta desactivada no podrá recibir clientes nuevos.
- Desactivar una ruta con clientes requerirá reasignarlos.
- El historial del cobrador anterior debe conservarse.

# 20. UC-16 — Reasignar cliente o cubrir una ruta

## Objetivo

Mover un cliente o grupo de clientes a otro cobrador de forma temporal o permanente.

## Actores

- Supervisor.
- Administrador.

## Flujo principal

1. El usuario selecciona una ruta.
2. Selecciona cliente específico, grupo de clientes o ruta completa.
3. Selecciona al nuevo cobrador.
4. Define si el cambio es permanente o temporal.
5. Si es temporal, captura fechas.
6. Registra el motivo.
7. Confirma.
8. El sistema retira acceso al cobrador anterior.
9. Habilita al nuevo cobrador.
10. Mantiene historial de ruta y responsables.

## Regla principal

Un crédito solo podrá estar disponible para cobro por un cobrador a la vez.

# 21. UC-17 — Restringir o rehabilitar cliente

## Objetivo

Impedir que un cliente con antecedentes graves reciba nuevos créditos.

## Flujo de restricción

1. Supervisor o administrador abre al cliente.
2. Selecciona **Restringir**.
3. Captura motivo, crédito relacionado, fecha de revisión y observaciones.
4. Confirma.
5. El cliente queda restringido.
6. El sistema bloquea nuevos créditos y renovaciones.

## Flujo de revisión

1. Se cumple la fecha de revisión.
2. El sistema muestra una alerta.
3. Supervisor o administrador revisa el caso.
4. Puede mantener la restricción, cambiarla a permanente o retirarla.
5. Registra motivo y decisión.

# 22. UC-18 — Administrar usuarios

## Actor

Administrador.

## Funciones

- Crear cobradores.
- Crear supervisores.
- Crear otros administradores, de acuerdo con la política.
- Modificar información.
- Cambiar rol.
- Bloquear usuario.
- Reactivar usuario.
- Restablecer acceso.
- Vincular o desvincular dispositivo.
- Asignar ruta.

## Reglas

- No se elimina físicamente un usuario con movimientos.
- Un usuario bloqueado no puede iniciar sesión.
- Cambiar de rol conserva el historial anterior.
- El administrador no debe conocer la contraseña del usuario.

# 23. UC-19 — Configurar planes, tasas y límites

## Actor

Administrador.

## Funciones

- Crear planes.
- Configurar plazos.
- Configurar tasas.
- Activar o desactivar planes.
- Configurar monto mínimo.
- Configurar incrementos.
- Configurar límite del cobrador.
- Configurar máximo de créditos activos.
- Configurar límite de efectivo.
- Configurar horarios de corte.

## Regla principal

Los cambios solo aplicarán a créditos nuevos.

# 24. UC-20 — Configurar días festivos

## Actor

Administrador.

## Flujo

1. El administrador abre el calendario.
2. Selecciona una fecha.
3. La marca como festiva.
4. Captura una descripción.
5. Confirma.
6. El sistema no genera cuota exigible, no cuenta falta, no afecta el semáforo y recorre el calendario.

## Excepción

Si la fecha ya tiene pagos registrados, el sistema deberá advertirlo antes de modificar el calendario.

# 25. UC-21 — Consultar tablero operativo

## Actores

- Supervisor.
- Administrador.

## Información principal

- Total cobrado hoy.
- Cobranza por ruta.
- Efectivo por cobrador.
- Transferencias.
- Comisiones.
- Créditos desembolsados.
- Clientes atrasados.
- Créditos vencidos.
- Entregas pendientes.
- Diferencias de caja.
- Cobradores cerca de $20,000.
- Número de noches conservando efectivo.

## Filtros

- Fecha.
- Ruta.
- Cobrador.
- Estado del crédito.
- Semáforo.
- Medio de pago.

# 26. UC-22 — Generar reportes

## Reportes iniciales

1. Cobranza diaria.
2. Cobranza semanal.
3. Cobranza mensual.
4. Cobranza por ruta.
5. Cobranza por cobrador.
6. Créditos activos.
7. Créditos liquidados.
8. Créditos vencidos.
9. Atrasos por cliente.
10. Atrasos por ruta.
11. Clientes restringidos.
12. Renovaciones.
13. Reestructuraciones.
14. Comisiones.
15. Transferencias.
16. Préstamos desembolsados.
17. Dinero en poder de cobradores.
18. Entregas de efectivo.
19. Diferencias de caja.
20. Gastos autorizados.

La exportación a PDF o Excel podrá incluirse desde Android, aunque no exista interfaz web.

# 27. UC-23 — Consultar auditoría

## Actores

- Administrador.
- Supervisor con acceso parcial.

## Información

- Usuario.
- Fecha y hora.
- Dispositivo.
- Módulo.
- Operación.
- Valor anterior.
- Valor nuevo.
- Motivo.
- Cliente.
- Crédito.
- Ruta.
- Corte relacionado.

## Operaciones auditadas

- Registro y modificación de clientes.
- Cambios de GPS.
- Solicitudes y autorizaciones.
- Desembolsos.
- Pagos y reversos.
- Transferencias.
- Reimpresiones.
- Renovaciones.
- Reestructuraciones.
- Rutas y reasignaciones.
- Entregas de efectivo.
- Gastos.
- Cortes y reaperturas.
- Restricciones.
- Cambios de configuración.

# 28. Diagrama integral de caja y cobranza

```text
INICIO DE JORNADA
  │
  ▼
Obtener saldo inicial del cobrador
  │
  ▼
Realizar ruta
  │
  ├── Cobro en efectivo
  │       │
  │       └── Sumar a efectivo esperado
  │
  ├── Transferencia
  │       │
  │       └── Reducir saldo del crédito
  │           sin sumar efectivo
  │
  ├── Comisión en efectivo
  │       │
  │       └── Sumar a efectivo esperado
  │
  ├── Desembolso
  │       │
  │       └── Restar de efectivo esperado
  │
  └── Gasto autorizado
          │
          └── Restar de efectivo esperado
  │
  ▼
¿Efectivo cercano a $20,000?
  │
  ├── Sí ──► Entrega parcial al supervisor
  │
  └── No ──► Continuar ruta
  │
  ▼
Terminar jornada
  │
  ▼
Contar efectivo
  │
  ▼
Comparar contado contra esperado
  │
  ├── Coincide ──────► Cerrar corte
  │
  └── No coincide ───► Generar incidencia
  │
  ▼
Definir dinero entregado
y dinero conservado
  │
  ▼
Validar límite de tres noches
  │
  ▼
Crear saldo inicial siguiente
  │
  ▼
FIN
```

# 29. Diagrama integral de estados de un crédito

```text
SOLICITUD
   │
   ├── Rechazada
   │
   ├── Devuelta para corrección
   │
   └── Autorizada
          │
          ▼
PENDIENTE DE DESEMBOLSO
          │
          ├── Cancelada
          │
          └── Desembolsada
                  │
                  ▼
                ACTIVA
                  │
          ┌───────┼─────────┐
          │       │         │
          ▼       ▼         ▼
      Al corriente Atrasada Vencida
          │       │         │
          └───────┴────┬────┘
                       │
                ┌──────┴──────┐
                │             │
                ▼             ▼
           Liquidada     Reestructurada
                │             │
                ▼             ▼
          Puede renovar   Nuevo calendario
```

# 30. Diagrama de gestión de rutas

```text
Crear ruta
   │
   ▼
Asignar cobrador titular
   │
   ▼
Asignar clientes
   │
   ▼
Ruta activa
   │
   ├── Operación normal
   │
   ├── Cambio permanente de cobrador
   │
   ├── Sustitución temporal
   │
   ├── Movimiento de clientes
   │
   └── Mezcla temporal de rutas
           │
           ▼
Actualizar permisos
           │
           ▼
Retirar acceso anterior
           │
           ▼
Habilitar acceso nuevo
           │
           ▼
Conservar historial
```

# 31. Dependencias entre casos de uso

| Caso principal | Casos relacionados |
|---|---|
| Registrar cliente | Actualizar cliente, crear crédito, asignar ruta |
| Crear crédito | Autorizar crédito, desembolsar, registrar comisión |
| Registrar pago | Imprimir ticket, actualizar caja, recalcular semáforo |
| Transferencia | Actualizar crédito, notificar ruta, actualizar corte |
| Entrega de efectivo | Caja del cobrador, caja del supervisor, incidencias |
| Corte diario | Pagos, desembolsos, gastos, entregas y transferencias |
| Renovación | Crédito liquidado, autorización y desembolso |
| Reestructuración | Saldo actual, nuevo plan y nuevo calendario |
| Reasignación | Rutas, permisos y clientes |
| Restricción | Créditos, renovaciones y autorizaciones |

# 32. Validaciones generales para todos los casos

1. El usuario debe tener una sesión activa.
2. La aplicación debe verificar el rol.
3. Las operaciones financieras requieren conexión.
4. Cada movimiento tendrá un folio único.
5. Los importes deben ser positivos.
6. Los saldos se validarán en el servidor.
7. No se eliminarán movimientos financieros.
8. Las correcciones se realizarán mediante reversos.
9. Las operaciones sensibles dejarán auditoría.
10. Los créditos anteriores conservarán sus condiciones.
11. El cobrador solo podrá operar sus clientes asignados.
12. Un crédito solo estará asignado a un cobrador a la vez.
13. No se podrá registrar un pago en un corte cerrado.
14. No se podrá entregar más efectivo del registrado.
15. No se podrá autorizar un sexto crédito activo.

# 33. Resultado de la fase

Con esta propuesta quedan definidos:

- Los actores.
- El catálogo de casos de uso.
- Los procesos principales.
- Los flujos normales.
- Las principales excepciones.
- Las validaciones.
- Las relaciones entre módulos.
- Los diagramas de operación en formato textual.

La siguiente etapa natural es diseñar los prototipos de pantallas y el modelo conceptual de base de datos, utilizando estos casos de uso como referencia.

---

# PARTE III — Diseño técnico y estrategia de desarrollo

# 1. Objetivo de la fase técnica

El objetivo de esta fase es transformar la línea base funcional de CREDIMEX en una solución técnica construible y verificable. Antes de pedirle a Cursor que genere módulos completos, se definirán el modelo de datos, la arquitectura, el contrato de la API, la navegación de Android, las reglas permanentes del repositorio y el plan de trabajo por etapas.

La secuencia general será:

```text
Requerimientos y reglas aprobadas
            ↓
Modelo conceptual y lógico de datos
            ↓
Arquitectura y decisiones técnicas
            ↓
Contrato de API
            ↓
Mapa de navegación y prototipos
            ↓
Repositorio y reglas de Cursor
            ↓
Desarrollo por bloques verticales
            ↓
Pruebas funcionales y financieras
            ↓
Piloto operativo
            ↓
Producción y evolución del producto
```

# 2. Actualización del alcance futuro

La versión 1 continuará enfocada en la aplicación Android y en la operación diaria de cobradores, supervisores y administrador. Sin embargo, se aprueba como evolución prioritaria un panel web para administración, debido a que las tablas, reportes, gráficas, usuarios, configuraciones y auditorías se consultan con mayor comodidad en una computadora.

## 2.1 Ruta de producto propuesta

| Versión | Objetivo principal | Usuarios |
|---|---|---|
| V1 | Operación Android, créditos, cobros, rutas, caja, cortes y administración mínima | Cobrador, supervisor y administrador |
| V1.1 | Estabilización, reportes, pruebas de campo y mejoras de operación | Todos los roles |
| V2 | Panel web administrativo conectado a la misma API | Administrador y supervisor |
| V2.1 | Integración oficial con WhatsApp para tickets y avisos | Administración y clientes |
| Futuro | Integración bancaria, optimización de rutas, aplicación de cliente e iPhone | Por definir |

## 2.2 Principio API-first

La lógica de negocio no deberá escribirse exclusivamente dentro de Android. La API será la fuente central de reglas y datos para que, en el futuro, Android y el panel web utilicen los mismos servicios.

```text
                    ┌─────────────────────┐
                    │ Aplicación Android  │
                    └──────────┬──────────┘
                               │
                               ▼
                    ┌─────────────────────┐
                    │ API central         │
                    │ reglas y permisos   │
                    └───────┬─────────────┘
                            │
             ┌──────────────┴──────────────┐
             ▼                             ▼
┌────────────────────────┐     ┌────────────────────────┐
│ Base de datos          │     │ Archivos privados     │
│ saldos y movimientos   │     │ INE y comprobantes    │
└────────────────────────┘     └────────────────────────┘
                            ▲
                            │
                    ┌───────┴─────────────┐
                    │ Panel web V2       │
                    │ admin/supervisor   │
                    └─────────────────────┘
```

# 3. Arquitectura propuesta

Se propone un monolito modular. Para el tamaño actual de CREDIMEX no se necesitan microservicios. Un solo backend organizado por módulos reducirá costo, complejidad y riesgo, pero mantendrá separación suficiente para crecer.

## 3.1 Componentes

| Componente | Propuesta | Responsabilidad |
|---|---|---|
| Aplicación Android | Kotlin y Jetpack Compose | Operación móvil por rol |
| Backend | Laravel 13 o versión estable aprobada al iniciar | API, reglas, seguridad y procesos |
| Base de datos | PostgreSQL | Datos transaccionales y consistencia |
| Autenticación | Laravel Sanctum | Tokens móviles y futura sesión web |
| Archivos | Almacenamiento privado compatible con S3 | INE, comprobantes y documentos |
| Caché móvil | Room | Consulta local sin confirmar movimientos financieros |
| Contrato de API | OpenAPI | Definición de endpoints y respuestas |
| Contenedores | Docker Compose | Entorno local y pruebas reproducibles |
| Panel web futuro | React y TypeScript | Administración, reportes y auditoría |
| Control de versiones | Git | Historial, ramas y revisión |
| Asistencia de desarrollo | Cursor | Planeación, implementación y revisión controlada |

## 3.2 Capas de Android

```text
android/
├── app/                 navegación, sesión y composición general
├── core-ui/             tema, componentes y estados comunes
├── core-network/        cliente de API y manejo de errores
├── core-database/       Room y caché local
├── core-domain/         modelos y reglas compartidas
├── feature-auth/        inicio de sesión y dispositivo
├── feature-clients/     clientes, documentos, referencias y GPS
├── feature-routes/      rutas y asignaciones
├── feature-credits/     solicitudes, autorización y desembolso
├── feature-payments/    pagos, transferencias y tickets
├── feature-cash/        efectivo, entregas y cortes
└── feature-reports/     consultas y tableros móviles
```

Cada pantalla deberá manejar un estado claro y enviar eventos al ViewModel. El ViewModel utilizará casos de uso y repositorios; la interfaz no accederá directamente a la API ni a Room.

## 3.3 Módulos del backend

```text
api/app/Modules/
├── Identity/            usuarios, roles, permisos y dispositivos
├── Clients/             clientes, contactos, referencias y documentos
├── Routes/              rutas, asignaciones y sustituciones
├── CreditPlans/         planes, tasas, plazos y límites
├── Credits/             solicitudes, autorizaciones y desembolsos
├── Payments/            pagos, reversos, transferencias y tickets
├── Cash/                movimientos, entregas, gastos y cortes
├── Collections/         visitas, atrasos, semáforo y restricciones
├── Renewals/            renovaciones y reestructuraciones
├── Reports/             tableros y exportaciones
└── Audit/               trazabilidad de operaciones
```

Los módulos pueden permanecer dentro de una sola aplicación Laravel. La separación será lógica y de carpetas, no de servidores.

# 4. Principios técnicos obligatorios

## 4.1 El servidor es la fuente de verdad

Android puede mostrar cálculos preliminares, pero el servidor volverá a validar antes de guardar:

- Usuario y permisos.
- Cliente y ruta asignada.
- Estado del crédito.
- Saldo vigente.
- Número de créditos activos.
- Restricción del cliente.
- Límite de autorización.
- Corte abierto.
- Efectivo disponible.
- Operación duplicada.

## 4.2 Manejo de dinero

No se utilizarán `float` ni `double` para importes financieros.

Se elegirá una sola estrategia para todo el proyecto:

- Centavos mediante enteros, o
- Tipo decimal exacto en base de datos y objetos de valor monetario en código.

Ejemplo con centavos:

```text
$1,234.50 = 123450 centavos
```

Las funciones de interés, comisión, total, cuota y saldo estarán centralizadas y tendrán pruebas automatizadas.

## 4.3 Transacciones atómicas

Registrar un pago deberá realizar en una sola transacción:

```text
1. Validar idempotencia.
2. Bloquear o verificar la versión del crédito.
3. Consultar el saldo vigente.
4. Crear el pago.
5. Actualizar el saldo.
6. Recalcular el atraso.
7. Crear el movimiento de efectivo.
8. Crear auditoría.
9. Confirmar todos los cambios.
```

Si falla cualquier paso, no deberá quedar un pago parcial ni un saldo incorrecto.

## 4.4 Idempotencia

Toda escritura financiera enviada desde Android llevará una clave única. Si una solicitud se repite por lentitud o reintento de red, la API devolverá el resultado anterior sin crear otro movimiento.

## 4.5 Reversos

Pagos, desembolsos, transferencias, entregas, gastos y cortes no se eliminarán físicamente. Las correcciones se representarán mediante reversos, reaperturas o ajustes auditados.

## 4.6 Condiciones históricas

Cada crédito guardará una copia de las condiciones autorizadas. Cambiar un plan no modificará créditos existentes.

## 4.7 Archivos privados

Las fotografías no se guardarán como columnas binarias dentro de PostgreSQL. La base almacenará metadatos y una ruta privada; el archivo estará en almacenamiento protegido y se entregará mediante autorización temporal.

# 5. Modelo de datos: siguiente entregable

Antes de generar migraciones se construirá el modelo conceptual y lógico.

## 5.1 Entidades iniciales

- Usuario.
- Rol y permiso.
- Dispositivo.
- Ruta.
- Asignación de ruta.
- Cliente.
- Domicilio y ubicación.
- Contacto alternativo.
- Referencia.
- Documento.
- Restricción del cliente.
- Plan y versión de plan.
- Solicitud de crédito.
- Autorización.
- Crédito.
- Calendario de cuotas.
- Desembolso.
- Comisión.
- Pago.
- Reverso.
- Transferencia.
- Ticket.
- Visita sin pago.
- Promesa de pago.
- Renovación.
- Reestructuración.
- Movimiento de efectivo.
- Entrega de efectivo.
- Gasto.
- Corte.
- Incidencia.
- Día festivo.
- Auditoría.

## 5.2 Entregables del modelo

1. Diagrama entidad-relación.
2. Diccionario de tablas y campos.
3. Claves primarias y foráneas.
4. Índices y restricciones únicas.
5. Catálogo de estados.
6. Reglas de integridad.
7. Estrategia de auditoría.
8. Estrategia de dinero y redondeo.
9. Datos de prueba.
10. Migraciones solo después de aprobar el modelo.

# 6. Contrato inicial de API

El contrato se definirá en OpenAPI antes de completar las pantallas. Los endpoints siguientes son una propuesta inicial:

```text
POST   /api/auth/login
POST   /api/auth/logout
GET    /api/me

GET    /api/routes
POST   /api/routes
PATCH  /api/routes/{route}
POST   /api/routes/{route}/assignments

GET    /api/clients
POST   /api/clients
GET    /api/clients/{client}
PATCH  /api/clients/{client}
POST   /api/clients/{client}/documents
POST   /api/clients/{client}/restrictions

POST   /api/clients/{client}/credit-applications
POST   /api/credit-applications/{application}/authorize
POST   /api/credits/{credit}/disburse
GET    /api/credits/{credit}

POST   /api/credits/{credit}/payments
POST   /api/payments/{payment}/reverse
POST   /api/transfers
POST   /api/credits/{credit}/visits

POST   /api/cash-deliveries
POST   /api/cash-deliveries/{delivery}/confirm
POST   /api/expenses
POST   /api/cash-cuts
POST   /api/cash-cuts/{cut}/reopen

POST   /api/credits/{credit}/renew
POST   /api/credits/{credit}/restructure

GET    /api/dashboard
GET    /api/reports
GET    /api/audit-events
```

Cada endpoint deberá documentar:

- Rol autorizado.
- Parámetros.
- Validaciones.
- Respuesta exitosa.
- Códigos de error.
- Idempotencia.
- Efectos financieros.
- Auditoría.
- Pruebas de aceptación.

# 7. Mapa inicial de pantallas

## 7.1 Pantallas comunes

- Inicio de sesión.
- Recuperación o cambio de acceso.
- Inicio según rol.
- Perfil y dispositivo.
- Notificaciones.
- Configuración de impresora.

## 7.2 Cobrador

- Mi ruta.
- Lista de clientes.
- Mapa de clientes.
- Expediente del cliente.
- Registrar o actualizar cliente.
- Créditos del cliente.
- Solicitar crédito.
- Desembolsar.
- Registrar pago.
- Imprimir ticket.
- Visita sin pago.
- Efectivo esperado.
- Entregar efectivo.
- Resumen de jornada.

## 7.3 Supervisor

- Resumen de rutas.
- Solicitudes pendientes.
- Transferencias.
- Entregas de efectivo.
- Cortes y diferencias.
- Clientes restringidos.
- Administración de rutas.
- Reasignaciones.
- Reportes operativos.

## 7.4 Administrador Android V1

- Tablero resumido.
- Usuarios.
- Planes y límites.
- Días festivos.
- Rutas.
- Cortes.
- Reportes esenciales.
- Auditoría básica.

## 7.5 Panel web administrativo V2

El panel web concentrará las tareas que se benefician de pantalla grande:

- Tablero general.
- Tablas extensas.
- Gráficas comparativas.
- Administración de usuarios y permisos.
- Configuración de planes y parámetros.
- Rutas y reasignaciones.
- Expedientes completos.
- Aprobaciones.
- Cortes y conciliación.
- Reportes y exportaciones.
- Auditoría completa.

El panel web no duplicará reglas; consumirá la misma API y respetará los mismos permisos.

# 8. Organización del repositorio

Se utilizará un monorepositorio para mantener código y documentación en un solo contexto para Cursor.

```text
credimex/
├── android/
├── api/
├── web-admin/               reservado para V2
├── docs/
│   ├── 00-index.md
│   ├── 01-product/
│   ├── 02-requirements/
│   ├── 03-use-cases/
│   ├── 04-processes/
│   ├── 05-data/
│   ├── 06-api/
│   ├── 07-architecture/
│   ├── 08-testing/
│   ├── 09-adr/
│   └── 10-backlog/
├── infra/
│   ├── docker/
│   ├── scripts/
│   └── deployment/
├── .cursor/
│   ├── rules/
│   └── commands/
├── AGENTS.md
├── README.md
├── .env.example
└── docker-compose.yml
```

# 9. Paquete de contexto para Cursor

Cursor no deberá depender de un único documento extenso ni de información recordada por el chat. El contexto oficial estará versionado dentro del repositorio.

## 9.1 Documento índice

`docs/00-index.md` indicará:

- Qué documentos son oficiales.
- Qué versión está aprobada.
- En qué archivo se encuentra cada regla.
- Qué se encuentra pendiente.
- Cómo actualizar la documentación.

## 9.2 AGENTS.md

Este archivo ofrecerá una vista rápida de todo el proyecto:

- Propósito de CREDIMEX.
- Stack aprobado.
- Estructura del repositorio.
- Comandos de instalación y pruebas.
- Reglas críticas de dinero.
- Flujo de trabajo.
- Archivos que deben consultarse antes de modificar un módulo.

## 9.3 Reglas de Cursor

```text
.cursor/rules/
├── 00-project-context.mdc
├── 10-business-rules.mdc
├── 20-financial-integrity.mdc
├── 30-backend-laravel.mdc
├── 40-android-kotlin.mdc
├── 50-web-admin.mdc
├── 60-security.mdc
├── 70-testing.mdc
└── 80-documentation.mdc
```

Las reglas generales estarán siempre activas. Las reglas técnicas utilizarán patrones de archivos para aplicarse solo a Android, backend o web.

## 9.4 Decisiones de arquitectura

Cada decisión relevante se registrará como ADR:

```text
docs/09-adr/
├── ADR-001-monolito-modular.md
├── ADR-002-postgresql.md
├── ADR-003-api-first.md
├── ADR-004-money-representation.md
├── ADR-005-idempotency.md
├── ADR-006-private-file-storage.md
└── ADR-007-future-web-admin.md
```

Esto permitirá que Cursor y los desarrolladores sepan por qué se tomó una decisión y no la cambien sin análisis.

# 10. Regla general propuesta para Cursor

```md
---
description: Reglas obligatorias del proyecto CREDIMEX
alwaysApply: true
---

# Contexto

CREDIMEX administra créditos de cobranza diaria, rutas,
pagos, efectivo y cortes.

# Reglas obligatorias

- El servidor es la fuente definitiva de saldos.
- No usar float o double para dinero.
- No eliminar movimientos financieros.
- Corregir mediante reversos o ajustes auditados.
- Usar transacciones en toda escritura financiera.
- Toda escritura financiera usa clave de idempotencia.
- Todo cambio sensible genera auditoría.
- Los créditos conservan sus condiciones originales.
- Máximo inicial de cinco créditos activos por cliente.
- El cobrador solo accede a clientes asignados.
- La comisión es independiente del saldo.
- Los días festivos no generan cuota ni atraso.
- No implementar reglas no documentadas.
- No cambiar el esquema sin migración aprobada.
- Generar pruebas para reglas financieras y permisos.
- Presentar plan antes de modificar varios módulos.
- Actualizar documentación cuando cambie el comportamiento.
```

# 11. Comandos reutilizables para Cursor

```text
.cursor/commands/
├── plan-use-case.md
├── implement-vertical-slice.md
├── review-financial-integrity.md
├── review-security.md
├── review-migration.md
├── write-tests.md
├── update-openapi.md
└── update-documentation.md
```

## 11.1 Flujo de un comando de implementación

1. Leer el caso de uso y reglas relacionadas.
2. Identificar módulos y archivos afectados.
3. Presentar plan sin modificar código.
4. Enumerar dudas o contradicciones.
5. Esperar aprobación.
6. Implementar un bloque pequeño.
7. Ejecutar pruebas.
8. Mostrar cambios y riesgos.
9. Actualizar documentación.

# 12. Forma de pedir trabajo a Cursor

No se utilizarán instrucciones como “crea toda la aplicación”. Cada tarea tendrá alcance, caso de uso, archivos permitidos y criterios de aceptación.

## 12.1 Prompt para planear

```text
Estamos desarrollando CREDIMEX.

Trabaja únicamente sobre el caso de uso UC-01 Registrar cliente.

Consulta primero:
- docs/00-index.md
- docs/02-requirements/
- docs/03-use-cases/UC-01.md
- docs/05-data/
- docs/06-api/
- .cursor/rules/

Antes de modificar archivos:
1. Explica el flujo funcional.
2. Identifica entidades, endpoints y pantallas afectadas.
3. Propón un plan en pasos pequeños.
4. Enumera dudas o contradicciones.
5. Indica las pruebas necesarias.
6. No escribas código hasta que el plan sea aprobado.
```

## 12.2 Prompt para implementar

```text
Implementa únicamente el paso aprobado del plan para UC-01.

Restricciones:
- No modifiques módulos no incluidos.
- No inventes campos ni reglas.
- Respeta arquitectura por capas.
- Agrega validaciones del servidor.
- Agrega permisos y auditoría.
- Incluye pruebas automatizadas.
- Ejecuta las pruebas y reporta resultados.
- Actualiza OpenAPI y documentación si aplica.
```

## 12.3 Prompt de revisión

```text
Revisa los cambios actuales sin modificar código.

Verifica:
1. Reglas de negocio.
2. Permisos.
3. Tipos monetarios.
4. Transacciones e idempotencia.
5. Auditoría.
6. Seguridad.
7. Pruebas normales y excepcionales.
8. Compatibilidad con el contrato OpenAPI.
9. Documentación actualizada.

Entrega hallazgos por severidad, archivo, causa y corrección.
```

# 13. División del desarrollo por fases

## Fase 3A — Diseño de datos y arquitectura

**Objetivo:** cerrar las decisiones necesarias antes del código funcional.

Entregables:

- Modelo entidad-relación.
- Diccionario de datos.
- Catálogo de estados.
- Estrategia de dinero y redondeo.
- Arquitectura modular.
- OpenAPI inicial.
- Mapa de navegación.
- Prototipos de baja fidelidad.
- ADR iniciales.

Criterio de salida: no existen dudas críticas sobre tablas, relaciones, saldos, estados o permisos.

## Fase 3B — Preparación del repositorio

Entregables:

- Monorepositorio.
- Laravel, PostgreSQL y Docker funcionando.
- Proyecto Android base.
- Carpetas reservadas para web V2.
- Reglas y comandos de Cursor.
- Integración continua básica.
- Ambientes local y pruebas.
- Manejo de secretos mediante variables de entorno.

Criterio de salida: un desarrollador puede clonar, instalar y ejecutar Android y API siguiendo README.

## Fase 4 — Identidad, seguridad y auditoría

Incluye:

- Usuarios.
- Roles y permisos.
- Inicio y cierre de sesión.
- Tokens móviles.
- Dispositivos autorizados.
- Bloqueo de usuario.
- Auditoría básica.
- Navegación según rol.

Criterio de salida: cada rol solo puede acceder a las funciones autorizadas.

## Fase 5 — Primer bloque vertical: rutas y clientes

Incluye extremo a extremo:

```text
Inicio de sesión
      ↓
Consultar rutas
      ↓
Registrar cliente
      ↓
Capturar contacto, referencia, INE, comprobante y GPS
      ↓
Guardar mediante API
      ↓
Consultar expediente
      ↓
Registrar auditoría
```

Este bloque validará Android, API, PostgreSQL, permisos, GPS, cámara, archivos y Cursor.

Criterio de salida: UC-01 y UC-02 aprobados mediante pruebas de aceptación.

## Fase 6 — Planes, solicitudes y créditos

Incluye:

- Planes y versiones.
- Montos y límites.
- Solicitudes.
- Autorizaciones.
- Máximo de créditos activos.
- Clientes restringidos.
- Cálculo financiero.
- Desembolso.
- Comisión.
- Calendario.

Criterio de salida: UC-03 y UC-04 aprobados con cálculos automatizados.

## Fase 7 — Cobranza y tickets

Incluye:

- Ruta diaria.
- Pagos.
- Semáforo.
- Visita sin pago.
- Transferencias.
- Reversos.
- Impresión Bluetooth.
- Prevención de duplicados.

Criterio de salida: UC-05 a UC-08 aprobados, incluyendo prueba con impresora real.

## Fase 8 — Efectivo y cortes

Incluye:

- Efectivo esperado.
- Límite de $20,000 configurable.
- Entregas parciales.
- Doble confirmación.
- Gastos.
- Permanencia nocturna.
- Cortes.
- Diferencias y reaperturas.

Criterio de salida: UC-09 a UC-12 aprobados y conciliación exacta.

## Fase 9 — Operaciones especiales y administración

Incluye:

- Renovaciones.
- Reestructuraciones.
- Restricciones.
- Días festivos.
- Reasignaciones.
- Tableros.
- Reportes.
- Auditoría completa.

Criterio de salida: operación completa del MVP Android.

## Fase 10 — Estabilización y piloto

Incluye:

- Migración inicial desde Excel cuando corresponda.
- Pruebas con datos reales controlados.
- Pruebas de concurrencia.
- Seguridad.
- Respaldos y restauración.
- Capacitación.
- Piloto con una ruta.
- Correcciones.
- Despliegue gradual.

Criterio de salida: corte y saldos coinciden durante el periodo de piloto.

## Fase 11 — Panel web administrativo V2

Incluye:

- Inicio de sesión web.
- Tablero general.
- Usuarios y permisos.
- Rutas y clientes.
- Planes y parámetros.
- Aprobaciones.
- Transferencias.
- Efectivo y cortes.
- Reportes y exportaciones.
- Auditoría completa.

El panel reutilizará la misma API. No se duplicarán cálculos ni reglas en React.

## Fase 12 — WhatsApp y automatizaciones

Incluye, sujeto a validación comercial y técnica:

- Envío de tickets.
- Avisos de pago.
- Recordatorios.
- Plantillas aprobadas.
- Registro de mensajes enviados.

# 14. Estrategia de pruebas

## 14.1 Unitarias

- Interés.
- Comisión.
- Total y cuota.
- Redondeo.
- Saldo.
- Semáforo.
- Días festivos.
- Límites de autorización.
- Máximo de créditos.
- Efectivo esperado.

## 14.2 Integración

- API y PostgreSQL.
- Transacciones.
- Bloqueos y concurrencia.
- Idempotencia.
- Permisos.
- Reversos.
- Auditoría.
- Archivos privados.

## 14.3 Android

- Formularios.
- Navegación.
- Estados de pantalla.
- Cámara y GPS.
- Manejo de errores.
- Bluetooth.
- Tickets.
- Diferentes tamaños de pantalla.

## 14.4 Aceptación

Las pruebas se derivarán de los casos de uso.

```text
Dado un cliente con cinco créditos activos
Cuando se intenta solicitar un sexto crédito
Entonces el sistema bloquea la solicitud
Y explica el motivo
Y no crea ningún crédito parcial
```

# 15. Git y control de cambios

## 15.1 Ramas

```text
main
staging
feature/uc-01-register-client
feature/uc-03-create-credit
fix/payment-idempotency
```

## 15.2 Flujo

```text
Caso de uso
   ↓
Plan de Cursor
   ↓
Revisión humana
   ↓
Implementación pequeña
   ↓
Pruebas
   ↓
Revisión financiera y de seguridad
   ↓
Commit
   ↓
Pull request
   ↓
Merge
```

## 15.3 Definición de terminado

Una tarea no está terminada hasta cumplir:

- Criterios de aceptación.
- Pruebas aprobadas.
- Sin errores de compilación.
- Permisos validados.
- Auditoría incluida cuando corresponde.
- OpenAPI actualizado.
- Documentación actualizada.
- Revisión de código.
- Sin secretos en el repositorio.

# 16. Ambientes y despliegue

Se manejarán al menos tres ambientes:

| Ambiente | Propósito | Datos |
|---|---|---|
| Local | Desarrollo individual | Ficticios |
| Pruebas o staging | Integración, aceptación y piloto | Ficticios o anonimizados |
| Producción | Operación real | Reales y protegidos |

No se utilizará la base de producción para desarrollar o probar.

La infraestructura mínima incluirá:

- API.
- PostgreSQL.
- Almacenamiento privado.
- HTTPS.
- Copias de seguridad.
- Registro de errores.
- Monitoreo de disponibilidad.
- Procedimiento de restauración.

# 17. Qué no se permitirá a Cursor

- Generar toda la aplicación en una sola solicitud.
- Inventar reglas de crédito.
- Cambiar cálculos sin actualizar requerimientos.
- Crear migraciones antes de aprobar el modelo.
- Usar tipos imprecisos para dinero.
- Eliminar movimientos financieros.
- Colocar reglas únicamente en la interfaz.
- Modificar producción.
- Guardar secretos en Git.
- Omitir pruebas para operaciones financieras.
- Cambiar varios módulos sin plan.
- Introducir una dependencia sin justificarla.
- Duplicar lógica entre Android, API y futura web.

# 18. Próximos entregables inmediatos

El proyecto se encuentra listo para iniciar Fase 3A. El orden inmediato será:

1. Modelo conceptual de datos.
2. Catálogo de estados.
3. Diagrama entidad-relación.
4. Diccionario de datos.
5. Arquitectura técnica detallada.
6. Contrato OpenAPI inicial.
7. Mapa de navegación Android.
8. Prototipos de baja fidelidad.
9. Backlog priorizado.
10. Repositorio y paquete de contexto para Cursor.

No se comenzará con pagos reales hasta que el modelo, las transacciones, la idempotencia y la auditoría estén aprobados.

# 19. Referencias técnicas consultadas

- Cursor Docs: Rules y AGENTS.md.
- Cursor Docs: Plan Mode.
- Android Developers: Jetpack Compose y recomendaciones de arquitectura.
- Laravel 13.x: Sanctum y uso de Laravel como backend de API.
- PostgreSQL: transacciones y control de concurrencia.

# 20. Resultado de la fase

Con esta ampliación, CREDIMEX dispone de:

- Línea base de requerimientos.
- Reglas de negocio.
- Casos de uso.
- Diagramas de procesos.
- Propuesta de arquitectura.
- Estrategia de datos y API.
- Preparación para Android y futuro panel web.
- Estructura de repositorio.
- Paquete de contexto para Cursor.
- Desarrollo dividido en etapas verificables.
- Criterios de salida y de calidad.

La siguiente actividad concreta será construir y validar el modelo de datos antes de generar código de producción.
