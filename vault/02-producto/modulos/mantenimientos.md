---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Mantenimientos

**Qué es:** historial de mantenimientos realizados, por **serial** (`equipment_id`) o por **bien dentro de un inventario** (`inventory_id + asset_id`). Rutas: `/api/maintenances/*`. Controlador: `MaintenanceController`.

> No confundir con [[programacion]] (solicitudes RA-F-33 con QR).

## Administrador
- ✅ Ver historial y registrar un mantenimiento (título, descripción, fecha).
- ✅ **Registro en lote:** la misma labor sobre ≥ 2 seriales del **mismo bien** (transacción + log).
- ✅ Eliminar un mantenimiento.

## Consultor
- 👁 Ver historial. La UI oculta registrar/eliminar.
- ⚠️ La API individual no valida rol → [[brechas-conocidas]].

## Super administrador
- 👁 Ver. ❌ Lote (exige `isAdministrator`). La API individual no lo bloquea.

Relacionado: [[inventarios]] · [[modulos]]
