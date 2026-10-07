---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Historial

**Qué es:** auditoría (`activity_logs`) de altas, cambios, eliminaciones, bajas y eventos, con IP, agente y valores antes/después. Rutas: `/records`, `/api/records/*`. Controlador: `RecordController`.

## Administrador
- ✅ Ver con filtros (usuario, acción, modelo, fechas, texto), 50 por página.
- ✅ Exportar PDF o CSV.
- ✅ Limpiar registros más antiguos de N días (por defecto 30).

## Super administrador
- 👁 Desde el portal: historial **de todas las sedes** (máx. 200 por sede).
- ❌ Exportar dentro de una sede (403 explícito).

## Consultor
- ❌ Menú oculto. ⚠️ La ruta de limpieza no valida rol → [[brechas-conocidas]].

Relacionado: [[modulos]]
