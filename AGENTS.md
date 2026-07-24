# CREDIMEX — Instrucciones para agentes

## Descripción

CREDIMEX es un sistema para una financiera de cobranza diaria.

El producto contempla:

- Aplicación Android para cobradores, supervisores y administradores.

- API central.

- Base de datos PostgreSQL.

- Panel web administrativo en una versión futura.

- Integración con WhatsApp en una versión futura.

## Fuente documental principal

Antes de planear o implementar una función, revisar:

- `docs/00-product/`

- `docs/01-requirements/`

- `docs/02-use-cases/`

- `docs/03-processes/`

- `docs/04-database/`

- `docs/06-architecture/`

- `docs/07-decisions/`

El documento maestro actual es:

- `docs/01-requirements/CREDIMEX_Documento_Maestro_v1.2.md`

## Reglas obligatorias

- El servidor es la fuente de verdad para saldos y operaciones.

- No utilizar `float` ni `double` para importes monetarios.

- No eliminar físicamente pagos, créditos, cortes ni movimientos financieros.

- Las correcciones financieras deben realizarse mediante reversos.

- Toda operación financiera debe generar auditoría.

- Toda escritura financiera debe ejecutarse dentro de una transacción.

- Todo pago debe utilizar una clave de idempotencia.

- Un cliente puede tener como máximo cinco créditos activos.

- El límite inicial del cobrador para autorizar créditos es de $4,000.

- La comisión es independiente del saldo del crédito.

- Un cobrador solo puede consultar clientes asignados.

- Un crédito solo puede estar disponible para un cobrador a la vez.

- Los cambios de planes no deben alterar créditos anteriores.

- No inventar reglas que no estén documentadas.

- Toda regla financiera debe tener pruebas automatizadas.

## Forma de trabajo

Antes de modificar código:

1. Leer el caso de uso relacionado.

2. Revisar las reglas de negocio.

3. Presentar un plan.

4. Enumerar los archivos que se modificarían.

5. Indicar dudas o contradicciones.

6. No escribir código hasta recibir autorización cuando la tarea sea amplia.

Después de implementar:

1. Ejecutar las pruebas correspondientes.

2. Mostrar los archivos modificados.

3. Resumir las decisiones tomadas.

4. Actualizar la documentación cuando corresponda.

5. No realizar cambios adicionales no solicitados.