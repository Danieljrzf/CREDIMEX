# CREDIMEX — Decisiones de Inicialización del Backend

**Versión:** 1.9
**Fecha:** 27 de julio de 2026
**Estado:** Aprobado
**Alcance:** Fase 3B.1 — Inicialización segura del backend Laravel.
Documenta el cierre técnico de la fase. No modifica D-01 a D-102 ni el
inventario de 67 tablas.
**Relación:** Complementa D-92 a D-102.

---

## Propósito

Documentar las decisiones D-103 a D-107 que fijan la versión efectiva
del backend, el carácter exclusivo de API, las rutas iniciales, la
eliminación del usuario predeterminado de Laravel y el manejo de claves
y archivos locales tras la inicialización segura.

Entregable asociado:

- `docs/06-architecture/cierre-fase-3b1-inicializacion-backend.md`

---

## D-103 — Versión inicial efectiva del backend

**Decisión:**

- Esqueleto de creación: `laravel/laravel` v13.0.0.
- Versión efectiva bloqueada: `laravel/framework` 13.22.0.
- PHP 8.5.1.
- Composer 2.10.2.
- `composer.lock` debe versionarse y es la referencia reproducible.
- Los parches podrán actualizarse posteriormente mediante una tarea
  controlada y validada.

**Motivo:** Fijar el punto de partida reproducible tras la
inicialización real, sin confundir la versión del esqueleto con la del
framework instalado.

---

## D-104 — Backend exclusivamente API

**Decisión:**

- `backend/` es una aplicación Laravel exclusivamente API.
- No contiene frontend Blade de demostración.
- No utiliza Node, Vite ni NPM en esta fase.
- Fueron eliminados `package.json`, `vite.config.js`, `resources/css`,
  `resources/js` y la vista `welcome`.
- Cualquier frontend administrativo futuro será otro alcance y no debe
  mezclarse automáticamente con este backend.

**Motivo:** Evitar acoplar la API móvil a un frontend Laravel de
demostración y mantener el alcance de V1 alineado con Android + API.

---

## D-105 — Rutas iniciales

**Decisión:**

- `routes/api.php` es el punto canónico de rutas futuras.
- `routes/api.php` inicia vacío.
- `routes/web.php` fue eliminado.
- No existen endpoints de negocio todavía.
- `/up` es únicamente la ruta técnica de salud proporcionada por Laravel.
- No se deben agregar rutas demostrativas.

**Motivo:** Dejar un contrato de entrada HTTP claro sin rutas de
demostración ni superficie web innecesaria.

---

## D-106 — Eliminación del usuario predeterminado

**Decisión:**

- `App\Models\User` fue eliminado.
- `UserFactory` y los ejemplos asociados fueron eliminados.
- `DatabaseSeeder` inicia vacío.
- El modelo futuro de autenticación será conceptualmente
  `App\Models\Usuario`.
- No crear `Usuario` hasta la fase autorizada.
- No instalar Sanctum todavía.
- No existe `personal_access_tokens`.

**Motivo:** El dominio CREDIMEX usa `usuarios` y `sesiones_token`, no
el modelo `User` ni las tablas predeterminadas de Laravel.

---

## D-107 — Claves y archivos locales

**Decisión:**

- `APP_KEY` se genera únicamente en `backend/.env` local.
- `APP_KEY` no se registra en documentación, ejemplos, salidas
  compartidas ni commits.
- Cuando una clave aparezca expuesta accidentalmente debe rotarse.
- `backend/.env` y `backend/.env.testing` permanecen ignorados.
- `.env.example` y `.env.testing.example` son versionables y no
  contienen secretos.
- `APP_KEY` de testing se generará cuando se prepare formalmente
  `credimex_test`.

**Motivo:** Proteger secretos locales y documentar la rotación ante
exposición accidental.

---

## Relación con D-01 a D-102

Estas decisiones **no alteran** D-01 a D-102 ni el inventario de 67
tablas. Cierran documentalmente la inicialización segura del backend.

---

## Referencias

- `docs/07-decisions/CREDIMEX_Decisiones_Infraestructura_v1.8.md`
- `docs/06-architecture/checklist-inicializacion-segura-3b1.md`
- `docs/06-architecture/cierre-fase-3b1-inicializacion-backend.md`
