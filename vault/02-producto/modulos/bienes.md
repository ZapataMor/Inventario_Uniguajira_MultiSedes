---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Bienes

**Qué es:** catálogo **único por sede** de bienes (`assets`). Cada bien es de tipo `Cantidad` (unidades agregadas) o `Serial` (equipo por equipo). Rutas: `/goods`, `/api/goods/*`. Controlador: `GoodsController`.

## Administrador
- ✅ Crear, editar y eliminar bienes (con imagen opcional).
- ✅ Eliminar solo si su cantidad total es 0.
- ✅ Carga masiva por Excel (`/goods/excel-upload`) y descarga de plantilla.
- ✅ Ver en qué inventarios está un bien (`/api/goods/{id}/locations`).

## Consultor
- 👁 Ver catálogo, totales y ubicaciones. Sin botones de edición.

## Super administrador
- 👁 Desde el portal: catálogo **consolidado de todas las sedes**, agrupado por sede.
- ❌ No crea/edita/elimina (backend 403) → [[brechas-conocidas]].

## Notas
- Lee de la vista SQL `assets_summary_view`.
- Las existencias reales se cargan dentro de un inventario → [[inventarios]].

Relacionado: [[modulos]] · [[roles-y-permisos]]
