# CREDIMEX — Estrategia de pruebas PostgreSQL

**Estado:** Aprobado (Fase 3B.0)
**Decisiones:** D-100.
**Alcance:** Estrategia. Sin pruebas creadas.

---

## 1. Principio

Todas las pruebas de migraciones, restricciones e integridad se
ejecutan contra PostgreSQL. SQLite está prohibido para este propósito.

---

## 2. Base de datos de pruebas

| Parámetro | Valor |
|---|---|
| Base | `credimex_test` |
| Rol | `credimex_app` (operación); `credimex_owner` para migraciones |
| Codificación | UTF8 |
| Zona | UTC |

---

## 3. Configuración de PHPUnit

`phpunit.xml` no debe contener:

- `DB_CONNECTION` con valor `sqlite`;
- `DB_DATABASE` con valor `:memory:`.

Las variables de entorno de pruebas apuntan a `credimex_test` mediante
`.env.testing`.

---

## 4. Validaciones obligatorias

| Aspecto | Qué se valida | Cómo |
|---|---|---|
| Identity | PK es `BIGINT GENERATED ALWAYS AS IDENTITY` | Consulta a `information_schema.columns` |
| TIMESTAMPTZ | `created_at` y `updated_at` son `timestamp with time zone` | Consulta a `information_schema.columns` |
| FK | Las claves foráneas existen con política `NO ACTION` | Consulta a `information_schema.referential_constraints` |
| CHECK | Las restricciones CHECK existen y rechazan valores inválidos | Inserción inválida → excepción |
| Índices parciales | Los índices únicos parciales existen y funcionan | Inserción duplicada dentro de la condición parcial → excepción |
| Rollback | `migrate:rollback` completo sin errores | Ejecución y verificación de ausencia de tablas |

---

## 5. Framework

PHPUnit 12.x (incluido en Laravel 13).

Trait recomendado: `RefreshDatabase` o `DatabaseTransactions` según el
tipo de prueba.

---

## 6. Pruebas pospuestas

- Pruebas de concurrencia (requieren lógica de negocio).
- Pruebas de rendimiento / particionamiento.
- Pruebas de cifrado y HMAC (requieren implementación de KMS).

---

## 7. Qué no se hace en esta fase

- No se crean pruebas.
- No se ejecuta PHPUnit.
- No se crean migraciones.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
- `docs/06-architecture/preparacion-tecnica-backend-postgresql.md`
