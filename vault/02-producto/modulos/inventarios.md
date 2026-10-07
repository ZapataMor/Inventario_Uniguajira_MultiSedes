---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Inventarios

**Qué es:** jerarquía **Grupo (bloque) → Inventario (salón) → Bienes → Seriales**. Rutas: `/groups`, `/group/{id}`, `/group/{g}/inventory/{i}`, `…/goods/{a}/serials`. Controladores: `GroupController`, `InventoryController`, `GoodsInventoryController`.

## Administrador
- **Grupos:** ✅ crear, renombrar, eliminar (solo si no tiene inventarios).
- **Inventarios:** ✅ crear, renombrar, cambiar responsable y estado de conservación, eliminar (solo si está vacío).
- **Bienes en inventario:**
  - ✅ Agregar bien tipo Cantidad (suma unidades) o Serial (un equipo con serial único y datos técnicos).
  - ✅ Editar cantidad o serial; quitar del inventario.
  - ✅ **Dar de baja** (cantidad o serial) → pasa a [[dados-de-baja]] con motivo.
  - ✅ Mover bienes/seriales a otro inventario (uno o en lote).
  - ✅ Carga masiva por Excel por inventario y por localización (`/groups/localizacion-excel-upload`).
- **Seriales:** ✅ ver equipos y registrar [[mantenimientos]] (también en lote).

## Consultor
- 👁 Navegar grupos, inventarios, bienes y seriales; buscar por grupo, inventario, bien o serial.

## Super administrador
- 👁 Desde el portal: grupos de **todas las sedes** y búsqueda consolidada.
- ❌ No modifica (backend 403).

Relacionado: [[bienes]] · [[modulos]]
