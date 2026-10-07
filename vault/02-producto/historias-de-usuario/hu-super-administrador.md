---
tipo: historia-usuario
creado: 2026-10-07
estado: implementada
tags: [historia, super-administrador]
---

# Historias — Super administrador

- **HU-0101** Como super admin, quiero iniciar sesión en el portal central, para ver todas las sedes desde un solo lugar. → [[portal]]
  - [ ] Si mi cuenta solo existe en una sede, se copia a la central al entrar.
- **HU-0102** Como super admin, quiero entrar a cualquier sede desde el portal, para revisarla sin otra cuenta. → [[subdominios]]
  - [ ] Si no existo en esa sede, se me crea al iniciar sesión.
- **HU-0103** Como super admin, quiero ver bienes, inventarios, bajas, reportes e historial consolidados por sede, para comparar sedes. → [[portal]]
- **HU-0104** Como super admin, quiero crear otro super admin con alcance "portal", para que tenga acceso a todas las sedes. → [[usuarios]]
- **HU-0105** Como super admin, quiero crear o editar usuarios de una sede concreta desde el portal, para administrar accesos sin entrar a la sede. → [[usuarios]]
- **HU-0106** Como super admin, quiero crear y gestionar programaciones de mantenimiento en una sede, para coordinar servicios. → [[programacion]]

## Restricciones vigentes
- No crea ni edita datos operativos (bienes, inventarios, reportes) ni elimina usuarios → [[brechas-conocidas]].
