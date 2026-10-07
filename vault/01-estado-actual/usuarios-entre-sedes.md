---
tipo: nota
creado: 2026-10-07
tags: [usuarios, multi-sede, faq]
---

# Usuarios entre sedes (preguntas frecuentes)

Base: **cada sede tiene su propia tabla `users`**, y la central tiene otra para el portal. Un mismo correo puede existir en varias bases con **ids distintos**. Lo que los une es el **correo** y la membresía en `user_tenant` (central).

## ¿Si creo un usuario, se crea en todas las bases?
| Dónde lo creo | Resultado |
|---|---|
| Dentro de una sede (admin) | Solo en esa sede + membresía en `user_tenant` |
| Portal, alcance "sede X" | Solo en la sede X (rol admin o consultor) |
| Portal, alcance "portal" (super admin) | En la central **y en todas las sedes activas**. Si una sede falla, se crea al iniciar sesión allí |

## ¿Si lo edito en una sede, se edita en las demás?
- **Nombre, usuario, correo, rol:** ❌ solo en la base donde lo editas.
- **Contraseña desde el módulo Usuarios:** ✅ se replica (por correo) en todas las sedes activas y en la central.
- **Contraseña desde "Mi perfil":** ❌ solo en la base actual.
- **Eliminar:** ❌ solo en la sede actual (y su membresía).
- Nadie puede cambiar su propio rol; solo un super admin edita a otro super admin.

## ¿Iniciar sesión es para una base o para todas?
- **En una sede:** valida contra la tabla `users` de **esa** sede. La sesión queda atada (`auth_tenant_id`); si con esa sesión entras a otra sede, te cierra la sesión y pide login de nuevo.
- **Super admin en una sede donde no existe:** si sus credenciales valen en la central u otra sede, se crea/actualiza automáticamente en esa sede.
- **En el portal:** solo entran super admins. Si el usuario solo existe en una sede, se copia a la central y recibe membresía en todas las sedes.
- **Super admin:** con `SESSION_DOMAIN` compartido, una sesión en el portal le sirve para saltar a cualquier sede (`/portal/sede/{slug}`).
- Usuario normal en el portal: el login falla (no es super admin).

## Hashes de contraseña
Si un hash no es bcrypt `$2y$` pero es válido (`$2a$`/`$2b$`), se acepta y se migra; si no, credenciales inválidas.

Relacionado: [[roles-y-permisos]] · [[usuarios]] · [[subdominios]]
