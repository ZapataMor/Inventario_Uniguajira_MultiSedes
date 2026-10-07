---
tipo: nota
creado: 2026-10-07
tags: [roles, permisos]
---

# Roles y permisos

## Roles
| Rol | Alcance | Cómo se determina |
|---|---|---|
| **Super administrador** | Global: portal + todas las sedes | `users.global_role = super_administrador` o correo fijo `recursosfisicos@uniguajira.edu.co` (`User::SUPER_ADMIN_EMAILS`) |
| **Administrador** | Una sede | `users.role = administrador` en la base de la sede (+ `user_tenant.role`) |
| **Consultor** | Una sede, solo lectura | `users.role = consultor` |
| **Persona externa** | Sin cuenta | Accede al formulario público de una programación por QR/enlace |

## Cómo se aplican
- Backend: `abort_if(! auth()->user()->isAdministrator(), 403)` en cada acción de escritura.
- Vistas: `@if(Auth::user()->isAdministrator())` oculta botones; el sidebar oculta Usuarios e Historial a consultores.
- Acceso a la sede: `EnsureTenantAccess` (membresía activa en `user_tenant` o rol válido en la base de la sede).

## ⚠️ Peculiaridad clave del super administrador
`isAdministrator()` devuelve **false** para el super admin. Resultado:
- En operación (bienes, inventarios, grupos, bajas, reportes) **solo puede consultar**, como un consultor.
- Sí puede: gestionar usuarios (crear/editar, no eliminar), crear/editar/eliminar programaciones, ver catálogos consolidados de todas las sedes desde el portal.
- Su inicio dentro de una sede es la vista de consultor; no puede exportar el historial dentro de una sede.
- Sus enlaces del menú llevan `?portal=1` → lo llevan a la vista **consolidada** del portal.

## Matriz resumida
| Módulo | Super admin | Administrador | Consultor |
|---|---|---|---|
| [[portal]] | ✅ | ❌ 403 | ❌ 403 |
| [[bienes]] / [[inventarios]] | 👁 ver (consolidado) | ✅ CRUD | 👁 ver |
| [[mantenimientos]] | 👁 + registro individual | ✅ | 👁 (ver [[brechas-conocidas]]) |
| [[programacion]] | ✅ | ✅ | 👁 ver |
| [[dados-de-baja]] | 👁 consolidado | ✅ + borrar registro | 👁 + exportar |
| [[reportes]] | 👁 consolidado | ✅ crear/renombrar/borrar | 👁 descargar |
| [[usuarios]] | ✅ crear/editar (cualquier sede) | ✅ en su sede | ❌ |
| [[historial]] | 👁 consolidado | ✅ ver/exportar/limpiar | ❌ (oculto) |
| [[inicio-y-tareas]] · [[perfil]] | ✅ | ✅ | ✅ |

Relacionado: [[usuarios-entre-sedes]] · [[modulos]]
