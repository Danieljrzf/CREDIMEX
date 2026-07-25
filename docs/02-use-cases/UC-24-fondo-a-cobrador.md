# UC-24 — Registrar fondo entregado al cobrador

## Objetivo

Registrar y confirmar la entrega de efectivo hacia un cobrador desde la
**caja central**, de modo que el término positivo de la fórmula de efectivo
esperado quede respaldado por una operación auditada
(`ENTREGA_EFECTIVO_A_COBRADOR`).

## Actores

- Supervisor o administrador (declara y entrega el fondo).
- Cobrador (recibe el fondo).
- Caja central (origen obligatorio del efectivo).

## Precondiciones

- Ambos usuarios tienen sesión activa.
- Existe conexión con el servidor.
- La caja central dispone de efectivo suficiente.
- Se selecciona un **motivo de entrega** válido (D-50).
- La jornada del cobrador está `ABIERTA` o se abrirá con esta operación.
- La jornada de caja central está `ABIERTA` o se abrirá con esta operación
  (creación perezosa, D-40).

## Origen y motivo

Toda entrega de fondo a un cobrador **sale de `CAJA_CENTRAL`**.

```text
CAJA_CENTRAL      - importe
EFECTIVO_COBRADOR + importe
```

`OrigenComercialFondo` **no** forma parte del flujo ordinario (D-50). Las
aportaciones, ingresos extraordinarios u otras entradas externas entran
primero a custodia real mediante sus propias operaciones; el fondo al
cobrador siempre sale de caja central.

Motivos de entrega permitidos:

- `FONDO_INICIAL`
- `FONDO_ADICIONAL`
- `PARA_DESEMBOLSO`
- `OPERACION_GENERAL`
- `OTRO`

Puede existir `operacion_relacionada_id` opcional.

## Flujo principal

### Etapa 1: declaración de entrega

1. El supervisor o administrador selecciona **Entregar fondo a cobrador**.
2. Selecciona al cobrador receptor.
3. Selecciona el **motivo de entrega**.
4. Captura el importe a entregar y, opcionalmente, las denominaciones.
5. Opcionalmente relaciona una operación previa.
6. Confirma. La entrega queda **Pendiente de recepción**.

### Etapa 2: recepción

7. El cobrador abre la entrega pendiente.
8. Cuenta el efectivo y captura el importe recibido.
9. El sistema calcula la diferencia entre lo declarado y lo recibido.

### Etapa 3: confirmación

10. Ambos confirman el importe realmente recibido mediante sesión personal,
    PIN o código.
11. El sistema ejecuta la confirmación como **una sola operación lógica**
    (`ENTREGA_EFECTIVO_A_COBRADOR`), generando un folio único.
12. Solo el **importe recibido** se mueve entre cuentas (D-47):
    - salida en `CAJA_CENTRAL`;
    - entrada en `EFECTIVO_COBRADOR`.
13. Si declarado = recibido → `CONFIRMADA`.
    Si declarado ≠ recibido → `CONFIRMADA_CON_DIFERENCIA` + `IncidenciaCaja`.
14. El sistema conserva usuarios, fecha, importe, motivo y auditoría, y
    genera comprobante.

## Flujos alternativos

### A1. Entrega parcial respecto a lo solicitado

El importe entregado puede ser menor al solicitado por el cobrador. Se
registra el importe realmente entregado y confirmado.

### A2. Diferencia entre declarado y recibido

Se aplica D-47: se mueve solo el recibido; la diferencia permanece en la
cuenta origen hasta resolver la incidencia.

## Excepciones

- **Motivo no seleccionado o no permitido:** no se puede declarar la entrega.
- **Sin conexión:** no se puede confirmar la entrega.
- **Efectivo insuficiente en caja central:** no se permite la entrega.
- **Falta de confirmación del receptor:** el fondo no incrementa el efectivo
  del cobrador.

## Postcondiciones

- El efectivo esperado del cobrador se incrementa por el importe recibido
  confirmado.
- La caja central refleja la salida por el mismo importe y folio.
- Queda registrado el motivo de entrega.
- Se genera comprobante y auditoría.
- Si hubo diferencia, queda una incidencia abierta.

## Reglas

- Toda entrega de fondo sale de `CAJA_CENTRAL` (D-50).
- El motivo de entrega es obligatorio.
- Una sola persona no puede cerrar toda la operación: se requiere doble
  confirmación.
- El fondo no incrementa el efectivo del cobrador hasta ser confirmado por el
  receptor.
- Solo se mueve el importe recibido (D-47).
- Las transferencias bancarias no forman parte de este caso de uso: aquí solo
  se maneja efectivo físico.
- Toda entrega de fondo genera auditoría y folio único.

## Criterios de aceptación

- No es posible confirmar una entrega sin motivo válido.
- El fondo confirmado aparece como término positivo en el efectivo esperado
  del cobrador.
- Existe una salida en caja central y una entrada al cobrador con el mismo
  folio.
- Una diferencia genera incidencia `CONFIRMADA_CON_DIFERENCIA` y no ajusta
  automáticamente el resto del saldo origen.
- El movimiento no puede confirmarse por un solo usuario.
- El importe declarado y el recibido quedan ambos registrados.
- Usuarios, fecha, importe, motivo y auditoría se conservan.
