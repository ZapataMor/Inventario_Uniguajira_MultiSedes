---
tipo: nota
creado: 2026-10-07
tags: [infraestructura]
---

# Infraestructura

## Stack
| Capa     | Tecnología                                                                                         |
| -------- | -------------------------------------------------------------------------------------------------- |
| Backend  | Laravel 12 · PHP ≥ 8.2                                                                             |
| Auth     | Laravel Fortify (login propio en `FortifyServiceProvider`), registro bloqueado (`/register` → 404) |
| Vistas   | Blade + Livewire Flux/Volt (pantallas de auth)                                                     |
| Frontend | JS propio, SPA ligera: `resources/js/navigation.js` (`loadContent`), Vite 7                        |
| Estilos  | **CSS puro** (Tailwind está en `package.json` pero no se usa)                                      |
| BD       | MySQL: 1 central + 1 por sede → [[base-de-datos]]                                                  |
| PDF      | dompdf, FPDF/FPDI (formato RA-F-33)                                                                |
| Excel    | PhpSpreadsheet (plantillas y cargas masivas)                                                       |
| Tests    | Pest 4                                                                                             |

## Despliegue (Hostinger)
- **Hosting compartido de Hostinger**, sin Docker. El servidor web sirve `public/` y aplica las reglas de `public/.htaccess`.
- **MySQL en el mismo servidor**: desde la app se conecta a `127.0.0.1`/`localhost`. El host `srvNNNN.hstgr.io` solo sirve para conexiones remotas.
- **Cada base tiene su propio usuario MySQL** (central y una por sede): sus credenciales van en `TENANT_{SLUG}_*` o en las columnas de `tenants` → [[base-de-datos]].
- **Assets:** se compilan en local (`npm run build`) y se sube `public/build` ya compilado.
- **Migraciones:** se corren a mano con `central:migrate` y `tenant:migrate --all`. Nada las ejecuta al desplegar.
- **Storage:** hay que crear `storage:link` y el enlace de `seeders` (ver `enlace_directo.md`).
- Sesiones, caché y colas viven en la **base central** (`SESSION_DRIVER=database`).

## Una sola app, varias sedes
- El mismo código atiende todas las sedes; la sede se decide por request → [[subdominios]].
- Middleware clave: `ResolveTenant` (activa la sede) y `EnsureTenantAccess` (valida acceso).
- Archivos por sede: `storage/app/tenants/{slug}/…` (reportes, evidencias, firmas).
- Firmas guardadas de usuario: `signatures/users/{sha1(email)}.png` (sirven en todas las sedes).

## Auditoría
- `ActivityLogger` registra altas, cambios, bajas y eventos en `activity_logs` de **cada sede**.

Relacionado: [[base-de-datos]] · [[roles-y-permisos]]
