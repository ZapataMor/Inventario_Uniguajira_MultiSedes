---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Usuarios

**Qué es:** gestión de cuentas. Rutas: `/users`, `/api/users/*`. Controlador: `UserController`. La replicación entre bases está en [[usuarios-entre-sedes]].

## Administrador (en su sede)
- ✅ Ver usuarios de la sede.
- ✅ Crear usuario (rol administrador o consultor).
- ✅ Editar nombre, usuario, correo, rol y contraseña (la contraseña se replica a otras sedes por correo).
- ✅ Eliminar, **excepto**: a sí mismo, a un super admin o al usuario llamado "Administrador".
- ❌ Cambiar su propio rol · editar a un super admin.

## Super administrador
- En una sede: igual que el administrador, **pero no puede eliminar** → [[brechas-conocidas]].
- En el portal: ✅ ve usuarios agrupados por sede (portal + cada sede).
- ✅ Crear con alcance **"portal"** → super admin en central y todas las sedes.
- ✅ Crear/editar con alcance **"sede X"** (`target_scope=tenant:{id}`).

## Consultor
- ❌ Sin acceso (menú oculto, backend 403).

Relacionado: [[roles-y-permisos]] · [[modulos]]
