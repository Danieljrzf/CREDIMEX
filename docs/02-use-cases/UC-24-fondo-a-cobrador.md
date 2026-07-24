# UC-24 — Registrar fondo entregado al cobrador

## Objetivo

Registrar y confirmar la entrega de efectivo hacia un cobrador con un **origen
financiero identificado y obligatorio**, de modo que el término positivo "dinero
entregado por supervisor" de la fórmula de efectivo esperado quede respaldado por
una operación auditada.

## Actores

- Supervisor o administrador (declara y entrega el fondo).
- Cobrador (recibe el fondo).
- Caja central, cuando el origen sea `CAJA_CENTRAL`.

## Precondiciones

- Ambos usuarios tienen sesión activa.
- Existe conexión con el servidor.
- Se selecciona un **origen financiero válido**.
- Si el origen es `CAJA_CENTRAL`, la caja central dispone de efectivo suficiente.
- La jornada del cobrador está `ABIERTA` o se abrirá con esta operación.

## Orígenes permitidos

Toda entrega de fondo a un cobrador debe tener un origen financiero identificado.
El origen **no es opcional**.

Orígenes permitidos:

- `CAJA_CENTRAL`
- `APORTACION_REGISTRADA`
- `RECUPERACION`
- `INGRESO_EXTRAORDINARIO_AUTORIZADO`

No se podrá aumentar el efectivo de un cobrador sin un origen registrado.

## Flujo principal

### Etapa 1: declaración de entrega

1. El supervisor o administrador selecciona **Entregar fondo a cobrador**.
2. Selecciona al cobrador receptor.
3. Selecciona el **origen financiero** entre los orígenes permitidos.
4. Captura el importe a entregar y, opcionalmente, las denominaciones.
5. Registra el concepto (fondo inicial, reabasto de ruta u otro autorizado).
6. Confirma. La entrega queda **Pendiente de recepción**.

### Etapa 2: recepción

7. El cobrador abre la entrega pendiente.
8. Cuenta el efectivo y captura el importe recibido.
9. El sistema calcula la diferencia entre lo declarado y lo recibido.

### Etapa 3: confirmación

10. Si los importes coinciden, ambos confirman mediante sesión personal, PIN o
    código.
11. El sistema ejecuta la confirmación como **una sola operación lógica**,
    generando un **folio de transferencia** único.
12. Según el origen:
    - si el origen es `CAJA_CENTRAL`, aplica el tratamiento descrito en la
      sección siguiente;
    - si el origen es `APORTACION_REGISTRADA`, `RECUPERACION` o
      `INGRESO_EXTRAORDINARIO_AUTORIZADO`, relaciona la entrada del cobrador con
      el movimiento de origen registrado y el mismo folio de transferencia.
13. El sistema bloquea el movimiento, conserva usuarios, fecha, importe y
    auditoría, y genera comprobante.

## Confirmación con origen CAJA_CENTRAL

Cuando el origen sea `CAJA_CENTRAL`, la confirmación de la entrega debe:

1. generar una **salida en caja central**;
2. generar una **entrada de efectivo al cobrador**;
3. relacionar ambos movimientos con el **mismo folio de transferencia**;
4. ejecutarse como **una sola operación lógica**;
5. conservar usuarios, fecha, importe y auditoría.

## Flujos alternativos

### A1. Entrega parcial respecto a lo solicitado

El importe entregado puede ser menor al solicitado por el cobrador. Se registra el
importe realmente entregado y confirmado, siempre con el origen seleccionado.

## Excepciones

- **Origen no seleccionado o no permitido:** no se puede declarar la entrega.
- **Diferencia entre declarado y recibido:** se genera una incidencia conforme a
  `cash-differences.md` y el fondo no afecta saldos hasta su resolución.
- **Sin conexión:** no se puede confirmar la entrega.
- **Efectivo insuficiente en caja central** (origen `CAJA_CENTRAL`): no se permite
  la entrega.
- **Falta de confirmación del receptor:** el fondo no incrementa el efectivo del
  cobrador.

## Postcondiciones

- El efectivo esperado del cobrador se incrementa por el fondo confirmado.
- Queda registrado el origen financiero de la entrada.
- Si el origen fue `CAJA_CENTRAL`, la caja central refleja la salida por el mismo
  importe y folio.
- Se genera comprobante y auditoría.
- Si hubo diferencia, queda una incidencia abierta.

## Reglas

- Toda entrega de fondo debe tener un origen financiero identificado; el origen no
  es opcional.
- No se podrá aumentar el efectivo de un cobrador sin un origen registrado.
- Una sola persona no puede cerrar toda la operación: se requiere doble
  confirmación.
- El fondo no incrementa el efectivo del cobrador hasta ser confirmado por el
  receptor.
- Cuando el origen es `CAJA_CENTRAL`, la salida de caja central y la entrada del
  cobrador se relacionan con el mismo folio y se confirman en una sola operación
  lógica.
- Las transferencias bancarias no forman parte de este caso de uso: aquí solo se
  maneja efectivo físico.
- Toda entrega de fondo genera auditoría y folio único.

## Criterios de aceptación

- No es posible confirmar una entrega sin origen financiero válido.
- El fondo confirmado aparece como término positivo en el efectivo esperado del
  cobrador con origen registrado.
- Con origen `CAJA_CENTRAL`, existe una salida en caja central y una entrada al
  cobrador con el mismo folio de transferencia.
- Una diferencia genera incidencia y no ajusta saldos automáticamente.
- El movimiento no puede confirmarse por un solo usuario.
- El importe declarado y el recibido quedan ambos registrados.
- Usuarios, fecha, importe y auditoría se conservan.
