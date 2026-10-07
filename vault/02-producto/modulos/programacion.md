---
tipo: modulo
creado: 2026-10-07
tags: [modulo]
---

# Módulo — Programación de mantenimientos

**Qué es:** solicitudes de servicio en **formato RA-F-33**. Se crea la solicitud, se genera un **QR de un solo uso** y una persona externa documenta la labor. Rutas: `/schedules`, `/api/schedules/*`, público `/programacion/{slug}/{code}`. Detalle técnico: `.claude/rules/programacion-mantenimientos.md`.

## Administrador y super administrador
- ✅ Crear solicitud por etapas: solicitante → tipos de servicio → actividad y localización (un bloque y uno o varios salones).
- ✅ Editar mientras esté abierta; eliminar siempre (purga evidencias).
- ✅ Ver QR/enlace mientras está pendiente.
- ✅ Ver la labor documentada y sus evidencias fotográficas.
- ✅ Descargar el **PDF RA-F-33** firmando "recibida por" (firma dibujada; opción de guardar la firma para todas las sedes).

## Consultor
- 👁 Ver el listado y el detalle. Sin crear/editar/eliminar.

## Persona externa (sin cuenta)
- ✅ Abrir el QR/enlace y llenar el reporte: equipo, acción, materiales, inicio/fin, quién lo hizo, fotos y **firma obligatoria**.
- ✅ Descargar su copia del RA-F-33.
- ❌ No puede volver a enviar: al enviar, la programación se **cierra para siempre**.

## Notas
- Solo existe dentro de una sede (sin sede redirige al portal).
- Evidencias y firmas en `storage/app/tenants/{slug}/schedules/{id}/`.

Relacionado: [[mantenimientos]] · [[modulos]]
