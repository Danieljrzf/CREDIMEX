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

El índice canónico de documentación es `docs/00-index.md`. Ningún agente debe usar rutas de carpetas distintas a las declaradas ahí.

La estructura documental oficial es:

- `docs/00-index.md`

- `docs/00-product/`

- `docs/01-requirements/`

- `docs/02-use-cases/`

- `docs/03-processes/`

- `docs/04-database/`

- `docs/05-api/`

- `docs/06-architecture/`

- `docs/07-decisions/`

- `docs/archive/`

El documento maestro vigente es:

- `docs/01-requirements/CREDIMEX_Documento_Maestro_Producto_y_Desarrollo_v1.2.md`

Las decisiones resueltas complementarias están en:

- `docs/01-requirements/CREDIMEX_Decisiones_Resueltas_v1.3.md`

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

## Idioma y documentación

- La documentación funcional y los manuales se redactan en español.

- Los mensajes de commit se redactan en español.

- Se permite conservar términos técnicos, nombres de clases, comandos y convenciones de código en inglés.

- Toda nueva decisión debe agregarse al historial documental.

- Toda funcionalidad terminada debe indicar su impacto en manuales.

- No actualizar manuales con funciones que todavía no estén implementadas y probadas.

## Ubicación del código

La aplicación backend (Laravel) vive exclusivamente en `backend/`.

La carpeta `api/` no es la aplicación backend; no debe recibir código
de servidor, controladores, modelos ni migraciones.

La carpeta `android/` se reserva para la aplicación móvil futura.

## PostgreSQL y conexiones Laravel

- Esquema de aplicación: `credimex` (`search_path` exclusivo; sin `public`).
- Conexión ordinaria: `pgsql` (roles app).
- Migraciones y DDL: únicamente `pgsql_owner` (roles owner).
- Nunca ejecutar migraciones con la conexión `pgsql`.
- Las migraciones futuras del dominio requerirán `pgsql_owner`.
- Desarrollo y testing usan roles y bases distintos, sin acceso cruzado.
- Secretos solo en `.env` / `.env.testing` locales; nunca en
  documentación, ejemplos ni commits.

## Pruebas Laravel y PostgreSQL

- Las pruebas Laravel deben usar exclusivamente la base `credimex_test`.
- Están prohibidos `RefreshDatabase`, `DatabaseMigrations` y
  `DatabaseTruncation` hasta que exista un mecanismo propio que migre
  con `pgsql_owner`.
- No desactivar ni evadir la guarda fail-closed de
  `Tests\TestCase` / `PostgreSqlTestSafetyGuard`.

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