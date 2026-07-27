# CREDIMEX — Sensibilidad, cifrado y logs

**Estado:** Aprobado (Fase 3A.3)
**Decisiones:** D-78, D-90.
**Alcance:** Política de datos sensibles. Sin diseño de KMS.

---

## 1. Principios

- Cifrado y HMAC se realizan en el **backend**.
- No almacenar llaves o secretos en PostgreSQL.
- Documentar `version_clave_cifrado` y `version_clave_busqueda` en
  columnas o metadatos asociados al valor protegido.
- Auditoría sanitiza datos sensibles.
- Exposición por rol: cobrador solo ve lo necesario de su cartera;
  supervisor/administrador según matriz de permisos (D-16 / maestro).

---

## 2. Tipos físicos de hashes (D-90)

Usar `BYTEA` para:

- HMAC de teléfono;
- HMAC de cuenta;
- HMAC de CLABE;
- hash de token;
- huella de idempotencia;
- hash de integridad de archivos.

Representación esperada SHA-256 / HMAC-SHA-256: **32 bytes**.

---

## 3. Matriz por categoría

| Categoría | Almacenamiento | Búsqueda | Exposición UI | Logs | Auditoría |
|---|---|---|---|---|---|
| Teléfono | Valor cifrado + HMAC + últimos 4 | HMAC | Enmascarado / últimos 4 según rol | Excluir valor pleno | Sanitizar |
| Cuenta bancaria / CLABE | Valor cifrado + HMAC + últimos 4 | HMAC | Últimos 4 / rol tesorería | Excluir | Sanitizar |
| Tokens de sesión | Hash no reversible (`BYTEA`) | Por hash | Nunca | Nunca en claro | No registrar token |
| INE / comprobante | Objeto privado externo; BD: metadatos + clave objeto + hash integridad | Por metadatos | Vista controlada | Sin contenido | Solo metadatos |
| Evidencias de operación | Igual que archivos privados | Por OF | Rol autorizado | Sin contenido | Metadatos |
| Nombre | Texto operable | Texto (acceso restringido) | Según rol | Excluir de logs rutinarios | Mínimo sanitizado |
| Dirección | Texto operable | Acceso restringido | Según rol | Excluir | Sanitizar |
| GPS | `NUMERIC` lat/lon | Acceso restringido | Mapa autorizado | Excluir | Evitar o limitar |
| Huella idempotencia | `BYTEA` | Por ámbito+clave | No | Técnico OK | OK |
| Folios / `id_publico` | Texto / UUID | Sí | Sí | OK | OK |
| Importes | `BIGINT` centavos | Sí | Según rol | Evitar dumps masivos | Permitido |

---

## 4. Columnas candidatas (conceptuales)

Para teléfonos / cuentas / CLABE (donde aplique):

- valor cifrado;
- `hmac_*` (`BYTEA`);
- `ultimos_cuatro`;
- `version_clave_cifrado`;
- `version_clave_busqueda`.

Para tokens:

- `token_hash` (`BYTEA`);
- sin columna de token en claro.

Para archivos:

- `clave_objeto` / URI privada;
- `hash_integridad` (`BYTEA`);
- tipo MIME / tamaño como metadatos;
- sin BYTEA del archivo completo en la fila.

---

## 5. JSONB y sensibilidad

Los snapshots de auditoría en JSONB deben estar **sanitizados** antes
de persistir (D-79 + D-78). No incluir teléfonos, CLABE, tokens ni
contenido de archivos en claro.

---

## 6. Fuera de alcance

- Infraestructura KMS / HSM.
- Rotación automática detallada de claves (solo se documenta el campo
  de versión).
- Cifrado transparente de disco (decisión de infraestructura posterior).

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Modelo_Fisico_v1.7.md`
- `docs/04-database/modelo-fisico-postgresql.md`
