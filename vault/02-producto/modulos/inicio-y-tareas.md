---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Inicio y tareas

**Qué es:** dashboard de la sede (`/home`) con tareas pendientes/completadas. Controladores: `HomeController`, `TaskController`.

## Administrador
- ✅ Dashboard de tareas: crear (fecha no pasada), editar, marcar pendiente/completada, eliminar.

## Consultor
- 👁 Vista de bienvenida con lo que puede consultar (bienes, inventarios, reportes).

## Super administrador
- Dentro de una sede ve la **vista de consultor**. Sin sede, `/home` lo manda al [[portal]].

## Notas
- Las tareas son **de la sede**, no por usuario; la API no valida rol → [[brechas-conocidas]].

Relacionado: [[modulos]]
