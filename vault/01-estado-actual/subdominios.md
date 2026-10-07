---
tipo: nota
creado: 2026-10-07
tags: [subdominios, multi-sede]
---

# Subdominios

## Cómo funcionan
`TenantResolver` decide en cada request si estás en **una sede** o en el **portal central**, en este orden:
1. Ruta `/portal*` o `?portal=1` → **portal** (borra `tenant_id` de la sesión).
2. Host exacto registrado y activo en `domains` → esa sede.
3. `tenant_id` guardado en sesión → esa sede.
4. Estrategia de `TENANCY_RESOLUTION`:
   - `subdomain`: `host − base_domain` = slug (o dominio registrado). Host == base → portal o sede por defecto.
   - `domain`: solo por `domains`.
   - `session`: lo elegido en el portal (útil en local, sin subdominios).
- Si el host coincide con `TENANCY_CENTRAL_DOMAIN` → portal. Si nada coincide → `TENANCY_DEFAULT_TENANT` o portal.

## Formato
- Producción: `{slug}-inventario.{TENANCY_BASE_DOMAIN}` (migración de abril 2026). Portal: `TENANCY_CENTRAL_DOMAIN`.
- Local: `TENANCY_RESOLUTION=session`, sin subdominios; se cambia de sede desde el portal.

## Cómo se agrega uno
1. Fila en central `domains`: `tenant_id`, `domain`, `is_primary`, `is_active`. Una sede puede tener varios; el **primario** es al que redirige el portal.
2. DNS / vhost apuntando a la **misma** app.
3. `SESSION_DOMAIN=.tu-dominio.com` si se quiere compartir sesión entre subdominios (necesario para que el super admin salte del portal a una sede).

## Qué cambia según el subdominio
- **Base de datos** activa (todo lo operativo) → [[base-de-datos]].
- **Branding**: nombre, logos, fondo y texto del login, colores, encabezado/pie de reportes, timezone.
- **Storage**: `storage/app/tenants/{slug}/`.
- **Login**: valida contra los usuarios de esa sede → [[usuarios-entre-sedes]].
- **Menú**: "Programación" solo existe dentro de una sede; sin sede, `/` y `/home` redirigen al portal.
- **Portal**: solo super administradores (cualquier otro recibe 403).

Relacionado: [[roles-y-permisos]] · [[portal]]
