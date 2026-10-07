---
tipo: nota
creado: 2026-10-07
tags: [brechas, pendiente]
---

# Brechas conocidas

Inconsistencias vistas al escanear el código (2026-10-07). Verificar antes de corregir.

- **Super admin no puede escribir en módulos operativos**: `isAdministrator()` lo excluye, así que crear bienes, grupos, inventarios o reportes le da 403. ¿Intencional? → [[roles-y-permisos]]
- **Super admin no puede eliminar usuarios**: `UserController::destroy` solo acepta `isAdministrator()`.
- **Mantenimiento individual sin control de rol**: `MaintenanceController::store` y `destroy` no validan rol; la UI lo oculta al consultor, pero la API lo permite. → [[mantenimientos]]
- **Limpiar historial sin control de rol**: `DELETE /api/records/clean` solo exige login; el menú lo oculta, la ruta no. → [[historial]]
- **Tareas sin control de rol ni dueño**: cualquier usuario puede editar/borrar cualquier tarea de la sede. → [[inicio-y-tareas]]
- **Borrar baja solo cubre tipo Cantidad**: `RemovedController::destroy` busca en `assets_removed`, no en `asset_equipments_removed`. → [[dados-de-baja]]
- **Ids de usuario no estables entre bases**: `user_tenant.user_id` mezcla ids de la central y de sedes.
- **Crear sede es manual** (SQL + comando). No hay pantalla ni comando `tenant:create`. → [[base-de-datos]]
- **Despliegue sin automatizar**: en Hostinger las migraciones (`central:migrate`, `tenant:migrate --all`), el build de assets y los enlaces de storage se hacen a mano. → [[infraestructura]]
- Código muerto: `EnsureTenantAccess` trata `admin@example.edu.co` como super admin solo si `User` no tuviera `isGlobalAdmin()` (sí lo tiene).
