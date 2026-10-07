---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Dados de baja

**Qué es:** historial de bienes retirados. Une `assets_removed` (tipo Cantidad) y `asset_equipments_removed` (tipo Serial, conserva datos técnicos). Rutas: `/removed`, `/api/removed/*`. Controlador: `RemovedController`.

> La baja se **hace** desde [[inventarios]]; aquí solo se consulta el historial.

## Administrador
- ✅ Ver listado, detalle, filtros y estadísticas.
- ✅ Exportar CSV.
- ✅ Eliminar un registro de baja (solo tipo Cantidad → [[brechas-conocidas]]).

## Consultor
- 👁 Ver, filtrar, estadísticas y exportar CSV.

## Super administrador
- 👁 Desde el portal: bajas **de todas las sedes** y detalle por sede.

Relacionado: [[modulos]]
