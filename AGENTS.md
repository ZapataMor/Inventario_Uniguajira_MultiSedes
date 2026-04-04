# AGENTS.md - Inventario Uniguajira (Laravel 12)

Sistema de gestion de inventario para la Universidad de la Guajira.

## Tech Stack

- **Backend:** Laravel 12, PHP 8.2+
- **Frontend:** Livewire 3, Volt, Flux UI, Tailwind CSS 4, Vite
- **Auth:** Laravel Fortify (with 2FA support)
- **Database:** MySQL (`inventario_db`)
- **Testing:** PEST PHP
- **Code quality:** Laravel Pint
- **Locale:** Spanish (`es` / `es_CO`)

## Common Commands

```bash
# Development
php artisan serve          # Start local server
npm run dev                # Start Vite dev server (Tailwind hot reload)
npm run build              # Build assets for production

# Database
php artisan migrate        # Run pending migrations
php artisan migrate:fresh --seed  # Reset DB and seed

# Code quality
./vendor/bin/pint          # Format PHP code (Laravel Pint)
./vendor/bin/pest          # Run tests

# Utilities
php artisan tinker         # REPL for the app
php artisan route:list     # Show all registered routes
php artisan cache:clear && php artisan config:clear  # Clear caches
```

## Project Structure

```
app/
  Http/Controllers/     # 12 controllers (see below)
  Models/               # 13 Eloquent models
  Helpers/              # ActivityLogger.php
  Listeners/            # LogAuthenticationActivity.php
  Livewire/             # Livewire components
  Observers/            # ModelActivityObserver.php
  Services/             # GoodsInventoryService.php
  Providers/
resources/
  views/
    components/         # Reusable blade components & modals
    goods/              # Goods listing & Excel upload
    home/               # Dashboard
    inventories/        # Groups, inventories, goods-in-inventory
    records/            # Activity log views
    removed/            # Removed goods views
    reports/            # Report folders
    users/              # User management views
    layouts/            # app, navbar, sidebar
routes/
  web.php               # ALL routes (web + /api/* prefix)
database/
  migrations/           # 19 migration files
```

## Controllers

| Controller | Responsibility |
|---|---|
| `HomeController` | Dashboard |
| `GoodsController` | Goods/assets CRUD |
| `GoodsInventoryController` | Goods within an inventory |
| `GroupController` | Inventory group management |
| `InventoryController` | Inventory management |
| `RecordController` | Activity logs (view, export, clear) |
| `RemovedController` | Removed goods tracking |
| `ReportController` | Reports CRUD |
| `ReportFolderController` | Report folder management |
| `TaskController` | Task management |
| `UserController` | User management (API) |

## Models

| Model | Table / Purpose |
|---|---|
| `User` | Authentication |
| `Asset` | Core goods/assets |
| `AssetEquipment` | Equipment-type asset details |
| `AssetInventory` | Asset -> Inventory junction |
| `AssetQuantity` | Quantity tracking per inventory |
| `AssetRemoved` | Removed assets |
| `AssetEquipmentRemoved` | Removed equipment tracking |
| `Group` | Inventory groups |
| `Inventory` | Inventory records |
| `Report` | Reports |
| `ReportFolder` | Report folder organization |
| `Task` | Task/to-do items |
| `ActivityLog` | Activity logging |

## Database Views (via migrations)

- `goods_summary_view` - Summary of goods
- `inventory_goods_view` - Goods within inventories
- `serial_goods_view` - Serial-tracked goods

## Routes Overview

All routes live in `routes/web.php`. API endpoints are grouped under the `/api` prefix (no separate `api.php` file).

**Web (auth required):**
- `GET /home` - Dashboard
- `GET /goods` - Goods list
- `GET /groups` - Inventory groups
- `GET /group/{groupId}` - Inventories in group
- `GET /group/{groupId}/inventory/{inventoryId}` - Goods in inventory
- `GET /reports` - Reports
- `GET /users` - User management
- `GET /records` - Activity records
- `GET /removed` - Removed goods

**API (selected):**
- Users: POST create/update, DELETE destroy
- Goods: POST create/batchCreate/update, DELETE destroy, GET json, download template
- Groups: POST create/rename, DELETE delete
- Inventories: POST create/rename/updateResponsable/updateEstado, DELETE delete
- Goods-Inventory: POST create/update-quantity/update-serial/remove-good, DELETE delete-quantity/delete-serial
- Removed: GET filter/filter-options/export/stats, DELETE destroy
- Records: DELETE clean, GET export
- Tasks: POST create, PUT update, PATCH toggle, DELETE destroy

## Activity Logging

All major actions (login, logout, create, update, delete, view) are logged via:
- `app/Helpers/ActivityLogger.php` - Static helper called in controllers
- `app/Listeners/LogAuthenticationActivity.php` - Auth events (login/logout)
- `app/Observers/ModelActivityObserver.php` - Model observer

## Styling Standard

**Tailwind CSS es el único método aceptado para escribir estilos en este proyecto.** No uses atributos `style=""` ni crees nuevas clases CSS para estilos visuales. Los archivos CSS legacy en `public/assets/css/` existen pero no deben extenderse.

### Reglas
- Usa clases Tailwind para todo: layout, espaciado, colores, tipografía y efectos.
- Prefiere valores estándar de Tailwind (e.g. `rounded-2xl`, `text-sm`, `tracking-widest`) sobre valores arbitrarios (`rounded-[30px]`, `text-[1.9rem]`, `tracking-[0.3em]`). Solo usa valores arbitrarios cuando no existe equivalente en la escala estándar.
- Nunca uses `style=""` en el HTML. Pasa variaciones de layout como props de clase Tailwind (e.g. `wrapperClass`).
- No agregues reglas nuevas a los archivos CSS legacy (`public/assets/css/**`). Esos archivos están congelados.
- Al modificar una vista o componente existente, reemplaza cualquier clase CSS o `style=""` que toques con su equivalente en Tailwind.

### Paleta de colores en uso
| Propósito | Token Tailwind |
|---|---|
| Acción principal | `emerald-600` / `emerald-700` |
| Destructivo | `rose-600` / `rose-700` |
| Advertencia | `amber-600` / `amber-700` |
| Texto | `slate-800` (títulos), `slate-600` (cuerpo), `slate-500` (muted) |
| Bordes | `slate-200` |
| Fondos | `white`, `slate-50`, `emerald-50` |

## Key Patterns

- **Modals:** Blade components in `resources/views/components/modal/` - one per action (create, edit, delete) per module
- **API responses:** JSON responses from controllers for AJAX calls in frontend
- **File uploads:** Excel import via `GET /goods/excel-upload`
- **Excel upload UI:** Reuse `x-excel-upload-area` and `x-excel-preview-table` for Excel screens instead of redefining upload or preview markup in each module
- **Excel upload JS:** Shared drag/drop, file reading, preview rendering, row removal, and error UI live in `public/assets/js/helpers/excel-ui.js`; each module should only provide parser, columns, and submit flow
- **Sessions:** Database-backed (`SESSION_DRIVER=database`)
- **Queue/Cache:** Database-backed

## Environment (.env.example)

```
APP_NAME=Inventario Uniguajira
APP_LOCALE=es
DB_CONNECTION=mysql
DB_DATABASE=inventario_db
DB_USERNAME=root
```

## Git Branches

- `master` - Main/production branch (use for PRs)
- `develop` - Active development branch
