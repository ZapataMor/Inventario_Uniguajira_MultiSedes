---
tipo: modulo
creado: 2026-10-07
tags: [modulo, multi-sede]
---

# Módulo — Portal central

**Qué es:** la app **sin sede activa** (`/portal` o `TENANCY_CENTRAL_DOMAIN`). Orquesta las sedes; no opera inventario. Controlador: `PortalController` + modo "portal" de varios controladores.

## Super administrador (único con acceso)
- ✅ Ver tarjetas de sedes activas con su branding.
- ✅ Entrar a una sede (`/portal/sede/{slug}`): redirige a su dominio primario o cambia en el mismo host (`inplace`).
- 👁 Catálogos **consolidados por sede**: [[bienes]], [[inventarios]], [[dados-de-baja]], [[reportes]], [[historial]].
- ✅ [[usuarios]] de todas las sedes (crear/editar con `target_scope`).

## Administrador y consultor
- ❌ 403 "Solo los super administradores pueden acceder al portal central". Su login en el portal falla.

Relacionado: [[subdominios]] · [[roles-y-permisos]]
