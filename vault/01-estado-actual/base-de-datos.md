---
tipo: nota
creado: 2026-10-07
tags: [base-de-datos, multi-sede]
---

# Base de datos

## ¿Cuántas bases puede tener?
**1 central + N de sede** (una por fila activa en `tenants`). No hay límite en el código. Hoy se siembran 3: `maicao`, `villanueva`, `fonseca`.

| Base                           | Conexión            | Qué guarda                                                                                                                          |
| ------------------------------ | ------------------- | ----------------------------------------------------------------------------------------------------------------------------------- |
| Central (`inventario_central`) | `central`           | `tenants`, `domains`, `tenant_branding`, `user_tenant` (membresías), `users` (super admins del portal), `sessions`, caché, colas    |
| Sede (`inventario_{slug}`)     | `tenant` (dinámica) | `users` de la sede, grupos, inventarios, bienes, seriales, bajas, mantenimientos, programaciones, reportes, tareas, `activity_logs` |

- Los modelos operativos usan `UsesTenantConnection`. `User` **no**: usa la conexión por defecto → en una sede lee la base de la sede; en el portal, la central.
- Vistas SQL por sede: `assets_summary_view`, `inventory_goods_view`, `serial_goods_view`.

## Cómo se resuelve la conexión de una sede
- **Nombre de la base:** `config tenancy.tenant_credentials.{slug}.database` → columna `tenants.database` → `inventario_` + slug.
- **Credenciales:** columnas `tenants.host/port/username/password` (password cifrada) → overrides por slug en `config/tenancy.php` (solo las 3 sedes) → `TENANT_DB_*` → plantilla.
- `TenantContext::set()` activa la conexión y la deja como **default** del request.

## Cómo se crea una sede (no hay pantalla ni comando)
1. Crear la base MySQL (y su usuario si el hosting lo exige).
2. Insertar en central `tenants`: `name`, `slug`, `database`, credenciales, `is_active=1`.
3. Insertar su dominio en `domains` → ver [[subdominios]].
4. Insertar su branding en `tenant_branding` (logos, colores, textos, timezone).
5. `php artisan tenant:migrate --tenant=slug --seed`.
6. Crear usuarios (desde el portal con alcance de esa sede) → [[usuarios-entre-sedes]].

## Comandos
| Comando | Uso |
|---|---|
| `central:migrate` | Migraciones de `database/migrations/central` |
| `tenant:migrate --tenant=slug \| --all [--fresh --seed]` | **El correcto** para migrar sedes |
| `app:migrate:fresh [--seed --ensure-databases]` | Resetea central + sedes (destructivo; puede crear bases faltantes) |
| `migrate:tenants` | ⚠️ En bases existentes **marca** migraciones nuevas como corridas sin ejecutarlas |

Relacionado: [[infraestructura]] · [[subdominios]] · [[brechas-conocidas]]
