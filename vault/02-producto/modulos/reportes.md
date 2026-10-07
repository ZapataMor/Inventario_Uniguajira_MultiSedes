---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Reportes

**Qué es:** carpetas con reportes generados y guardados en el storage de la sede. Rutas: `/reports`, `/reports/folder/{id}`, `/api/folders/*`, `/api/reports/*`. Controladores: `ReportFolderController`, `ReportController`.

**Tipos de reporte:** un inventario, un grupo, todos los inventarios, bienes, seriales, bienes dados de baja, historial. Formato **PDF o Excel**, con branding de la sede.

## Administrador
- ✅ Crear, renombrar y eliminar carpetas.
- ✅ Generar, renombrar y eliminar reportes.
- ✅ Descargar.

## Consultor
- 👁 Ver carpetas y **descargar** reportes ya generados.

## Super administrador
- 👁 Desde el portal: carpetas **de todas las sedes**; puede abrirlas y descargar.
- ❌ No genera ni borra (backend 403).

Relacionado: [[modulos]]
