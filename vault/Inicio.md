---
tipo: moc
creado: 2026-10-07
tags: [moc, inicio]
---

# Inventario Uniguajira · Multi-Sedes

Sistema de inventario de la Universidad de La Guajira: **una base central** que gobierna las sedes y **una base operativa por sede** (Maicao, Villanueva, Fonseca…).

## Estado actual (cómo está construido)
- [[infraestructura]] — stack, despliegue, almacenamiento.
- [[base-de-datos]] — central vs. sede, cuántas puede haber, cómo crear una sede.
- [[subdominios]] — cómo el host decide la sede y qué cambia.
- [[roles-y-permisos]] — super administrador, administrador, consultor, persona externa.
- [[usuarios-entre-sedes]] — ¿se replica al crear/editar? ¿el login vale para todas?
- [[brechas-conocidas]] — inconsistencias detectadas en el código.

## Producto (qué hace)
- [[modulos]] — MOC de módulos, con permisos por rol.
- [[historias-de-usuario]] — MOC de historias por rol.

## Empezar aquí
1. Lee [[base-de-datos]] y [[subdominios]]: todo lo demás depende de "en qué sede estoy".
2. Revisa [[roles-y-permisos]] y luego el módulo que vayas a tocar en [[modulos]].
3. Si algo te sorprende, anótalo en [[brechas-conocidas]].

> Fuente: escaneo del código en `master` (2026-10-07). Si cambia la arquitectura, actualiza la nota.
