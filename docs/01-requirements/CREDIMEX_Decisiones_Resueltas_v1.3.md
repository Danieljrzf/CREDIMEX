# CREDIMEX — Decisiones Resueltas

**Versión:** 1.3
**Fecha:** 24 de julio de 2026
**Estado:** Aprobado
**Relación:** Complementa `CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`

---

## Propósito

Este documento cierra decisiones que en el documento maestro v1.2 quedaban
abiertas o ambiguas. Las decisiones aquí registradas son de aplicación obligatoria
y prevalecen sobre interpretaciones no documentadas. No introducen reglas nuevas
más allá de lo necesario para eliminar la ambigüedad.

El documento maestro v1.2 no se modifica en esta etapa. Cuando el maestro se
actualice, deberá incorporar estas decisiones.

---

## Decisiones

### D-01 — Naturaleza del plazo

Los plazos de los planes (20, 27, 40, 54 y 68) se expresan como **número de
cuotas programadas**, no como días naturales.

### D-02 — Días de cobranza

Se cobra de **lunes a sábado**.

### D-03 — Días sin cuota

Los **domingos y los días festivos no generan cuota**. La fecha final del crédito
se recorre para conservar el número de cuotas programadas.

### D-04 — Representación monetaria

El dinero se almacena en **centavos enteros**. No se utilizan `float` ni `double`
para importes monetarios.

### D-05 — Redondeo de la cuota ordinaria

La cuota ordinaria se **redondea hacia arriba al peso** completo.

### D-06 — Ajuste de la última cuota

La **última cuota se ajusta al saldo restante**, de modo que la suma de todas las
cuotas sea exactamente igual al total a pagar.

### D-07 — Orden de aplicación de pagos a cuotas

Los pagos cubren las **cuotas vencidas desde la más antigua hasta la más nueva**
(FIFO). El saldo del crédito continúa siendo único: los pagos no se dividen
contablemente entre capital e interés. La aplicación FIFO a cuotas se utiliza
únicamente para determinar qué cuotas programadas están cubiertas y para calcular
el atraso y el semáforo.

### D-08 — Cuota parcialmente cubierta

Una cuota **parcialmente cubierta sigue pendiente** hasta que se cubra por
completo mediante pagos posteriores.

### D-09 — Créditos que cuentan para el máximo de cinco

Cuentan para el máximo de cinco créditos activos por cliente únicamente los
créditos **desembolsados que conserven saldo** y se encuentren en un estado
operativo activo:

- `ACTIVO`
- `ATRASADO`
- `VENCIDO`
- `REESTRUCTURADO_CON_SALDO`

No cuentan:

- solicitudes;
- pendientes de autorización;
- autorizados sin desembolso;
- rechazados;
- cancelados;
- liquidados;
- castigados.

Un crédito castigado puede conservar saldo histórico, pero queda fuera del límite
de cinco porque ya no es un crédito operativo activo.

### D-10 — Naturaleza de la reestructuración

La reestructura **conserva el mismo crédito** y crea una **nueva versión de
calendario**. No genera un crédito nuevo ni elimina la operación anterior; las
condiciones anteriores se conservan como historial.

### D-11 — Liquidación de la comisión

La comisión queda **liquidada al momento del desembolso** y **no puede quedar
pendiente** ni cobrarse posteriormente.

La modalidad "préstamo completo y comisión aparte" significa que la comisión se
**registra y liquida por separado, pero dentro de la misma operación de
desembolso**. "Aparte" se refiere al registro separado del movimiento, no a un
cobro diferido.

### D-12 — Autoautorización del cobrador

El cobrador puede **autoautorizar créditos dentro de su límite configurable**
(inicialmente de $1,000 a $4,000). Los importes superiores escalan a supervisor o
administrador.

### D-13 — Incrementos de monto

Los incrementos iniciales de monto son de **$1,000** y son **configurables** por
el administrador.

### D-14 — Permanencia nocturna de efectivo

Se permite conservar efectivo por un máximo de **tres noches**.

### D-15 — Cuarta noche

Una **cuarta noche queda bloqueada**, salvo **excepción administrativa auditada**.

### D-16 — Auditoría operativa parcial del supervisor

El supervisor tiene **auditoría operativa parcial**.

Puede consultar auditoría operativa relacionada con:

- clientes y documentos;
- rutas y reasignaciones;
- créditos y autorizaciones;
- desembolsos y comisiones;
- pagos, reversos y transferencias;
- entregas de efectivo;
- gastos y cortes;
- renovaciones y reestructuraciones;
- restricciones de clientes.

No puede consultar:

- contraseñas o restablecimientos;
- tokens;
- sesiones administrativas;
- secretos o variables de entorno;
- configuración técnica del servidor;
- cambios sensibles relacionados con administradores.

### D-17 — Registro del desembolso

El desembolso registra por separado **principal, comisión y primer pago
retenido**, cada uno como movimiento identificable dentro de la misma operación.

### D-18 — Ciclo de la jornada operativa

La jornada se **abre con la primera operación financiera** del cobrador en la
fecha operativa y se **cierra mediante corte**. Los estados de la jornada se
definen en `collector-workday.md`.

### D-19 — Búsqueda de duplicados

La búsqueda de duplicados **no requiere por ahora CURP ni OCR estructurado**. Se
mantiene la búsqueda por nombre, teléfono y dirección descrita en el maestro.

### D-20 — Alcance de plataformas

La **aplicación iPhone queda en el futuro** y el **panel web en la versión 2**. La
versión 1 es exclusivamente Android más la API central.

---

## Tensiones documentales pendientes de reflejar en el maestro

Estas tensiones quedan resueltas por las decisiones anteriores, pero el documento
maestro v1.2 todavía conserva la redacción original:

- RN-CLI-008 del maestro habla de "créditos activos" sin enumerar estados. D-09
  precisa los estados que cuentan y confirma que los castigados quedan fuera.
- RN-COM-003 del maestro describe la modalidad "comisión aparte". D-11 aclara que
  esa modalidad no implica comisión pendiente.
- RN-PAG-001 del maestro indica que el pago reduce el saldo total sin dividir
  capital e interés. D-07 y D-08 lo mantienen y añaden la aplicación FIFO a cuotas
  solo para efectos de atraso y semáforo.
