# CLAUDE.md - Inventario Uniguajira (Laravel 12)

Sistema de gestion de inventario para la Universidad de la Guajira.

## Routes

All routes live in `routes/web.php`. API endpoints are grouped under the `/api` prefix (no separate `api.php` file).

## Styling Standard

**CSS puro es el método aceptado para escribir estilos en este proyecto.** No usar Tailwind CSS mientras la aplicación no funcione correctamente con esa integración. Se permite crear y mantener estilos en archivos CSS del proyecto según sea necesario.
**Nota de redacción:** no usar la grafía incorrecta "tailwindo" en documentación o comentarios. Tailwind CSS no debe usarse actualmente en este proyecto.

### Reglas
- Usa CSS puro para layout, espaciado, colores, tipografía y efectos visuales.
- Evita depender de Tailwind CSS o de utilidades generadas por Tailwind en vistas nuevas o modificadas.
- Evita `style=""` en el HTML salvo casos puntuales y justificados; prefiere reglas reutilizables en archivos CSS.
- Si modificas una vista o componente existente, prioriza consolidar los estilos en CSS mantenible y consistente con la estructura actual del proyecto.

### Paleta de colores en uso
| Propósito | Token Tailwind |
|---|---|
| Acción principal | `emerald-600` / `emerald-700` |
| Destructivo | `rose-600` / `rose-700` |
| Advertencia | `amber-600` / `amber-700` |
| Texto | `slate-800` (títulos), `slate-600` (cuerpo), `slate-500` (muted) |
| Bordes | `slate-200` |
| Fondos | `white`, `slate-50`, `emerald-50` |

## Key Patterns

- **Modals:** Blade components in `resources/views/components/modal/` - one per action (create, edit, delete) per module
- **API responses:** JSON responses from controllers for AJAX calls in frontend
- **File uploads:** Excel import via `GET /goods/excel-upload`
- **Excel upload UI:** Reuse `x-excel-upload-area` and `x-excel-preview-table` for Excel screens instead of redefining upload or preview markup in each module
- **Excel upload JS:** Shared drag/drop, file reading, preview rendering, row removal, and error UI live in `public/assets/js/helpers/excel-ui.js`; each module should only provide parser, columns, and submit flow
- **Sessions:** Database-backed (`SESSION_DRIVER=database`)
- **Queue/Cache:** Database-backed

## Controller Behavior Context

- **Patron de navegacion hibrida:** la mayoria de vistas principales (`HomeController`, `GoodsController`, `GroupController`, `InventoryController`, `GoodsInventoryController`, `UserController`, `ProfileController`, `RecordController`, `RemovedController`, `ReportController`, `ReportFolderController`) responden de dos formas: vista completa en carga normal y solo `renderSections()['content']` cuando la peticion es AJAX. Esto sostiene una SPA ligera montada sobre Blade.
- **Auditoria transversal:** las operaciones importantes usan `ActivityLogger` para registrar altas, cambios, eliminaciones, bajas y eventos personalizados. Cuando se toca CRUD o procesos masivos, normalmente hay que mantener ese log.
- **Servicios y vistas SQL:** gran parte de la logica pesada evita joins manuales repetidos usando vistas SQL (`assets_summary_view`, `inventory_goods_view`, `serial_goods_view`) y `GoodsInventoryService` para altas/actualizaciones de bienes dentro de inventarios.

### Controllers by Responsibility

- `HomeController`: dashboard de tareas; separa la experiencia de `administrador` y `consultor`.
- `GoodsController`: catalogo global de bienes. Maneja CRUD, imagen opcional en disco `public`, JSON para selects/autocomplete, plantillas Excel y carga masiva. Las versiones `batchCreateGlobal*` tienen codigo legado debajo de un `return $this->batchCreate($request);`, asi que hoy reutilizan la carga masiva simple.
- `GoodsInventoryController`: operaciones dentro de un inventario. Crea bienes tipo cantidad o serial, actualiza cantidades/seriales, elimina relaciones, da de baja bienes de cantidad o serial, descarga plantilla Excel y soporta carga masiva normal y optimizada por inventario.
- `GroupController`: CRUD simple de grupos, mas endpoint JSON `getAll()` para selects de reportes.
- `InventoryController`: lista inventarios por grupo con contadores agregados, muestra seriales por bien, crea/renombra inventarios, cambia responsable y estado de conservacion, elimina inventarios vacios y expone `getByGroupId()` para reportes.
- `RecordController`: historial de actividad con filtros por usuario, accion, modelo, fechas y texto. Exporta a PDF o CSV y permite limpiar registros antiguos.
- `RemovedController`: historial de bajas unificando `assets_removed` y `asset_equipments_removed`. Tiene vista principal, detalle por modal, filtros AJAX, opciones para selects, export CSV, estadisticas y borrado de registros de baja.
- `ReportController`: genera PDFs persistidos en storage local por carpeta. Soporta reportes de inventario, grupo, todos los inventarios, bienes, seriales y bienes dados de baja. Usa branding por tenant y `SimplePdfService`.
- `ReportFolderController`: CRUD basico de carpetas de reportes y vistas para listar reportes por carpeta.
- `TaskController`: CRUD de tareas del dashboard con validacion de fecha no pasada y toggle `pending/completed`.
- `MaintenanceController`: historial de mantenimientos. Lee por serial individual (`equipment_id`) o por bien dentro de un inventario (`inventory_id + asset_id`). Ademas de la creacion unitaria, expone `batchStore()`: registra una misma labor sobre varios seriales marcados en la vista de seriales. Exige minimo dos equipos, que todos pertenezcan al mismo bien y rol `administrador`; inserta una fila por equipo dentro de una transaccion y deja un `ActivityLogger::custom` con los seriales afectados.
- **Programacion de mantenimientos** (`InventoryScheduleController`, `PublicScheduleController`, formato RA-F-33, firmas y evidencias): el detalle vive en `.claude/rules/programacion-mantenimientos.md`, que se carga al trabajar con los archivos de ese modulo.
- `UserController`: administracion de usuarios exclusiva para `administrador`; impide cambiar el propio rol y bloquear la eliminacion del usuario base o del usuario autenticado.
- `ProfileController`: perfil del usuario autenticado; actualiza datos basicos y contrasena propia con respuestas JSON.
- `AssetImageController`: sirve imagenes de bienes desde storage de forma segura, evita path traversal y cae en una imagen por defecto cuando no existe el archivo.
- `PortalController`: dashboard central multi-sede/multi-tenant. Consulta estadisticas y grupos por tenant conectandose dinamicamente a la base de cada sede y permite cambiar de tenant.

## AJAX Navigation Context

- El archivo fuente equivalente a `loadcontent.js` es `resources/js/navigation.js`. No existe un archivo con ese nombre exacto en el repo; la version compilada queda dentro de `public/build/assets/app-*.js`.
- `window.loadContent(url, options)` hace fetch con header `X-Requested-With: XMLHttpRequest`, inyecta el HTML recibido en `#main-content`, muestra/oculta `#loader` y opcionalmente actualiza `history.pushState`.
- `window.initializeScripts(url)` decide que inicializador volver a correr segun el primer segmento de la ruta. El mapa actual es:
  - `home` -> `initFormsTask`
  - `schedules` -> `initSchedulesModule`
  - `goods` -> `initFormsBien`
  - `groups` -> `initGroupFunctions`
  - `profile` -> `initProfileFunctions`
  - `users` -> `initUserFunctions`
  - `records` -> `initHistorialFunctions`
  - `reports` -> `initReportsModule`
- En `DOMContentLoaded`, los enlaces con `a[data-nav]` interceptan el click para cargar contenido parcial en vez de hacer navegacion completa.
- En `popstate`, el script vuelve a cargar la vista sin reescribir el historial y sincroniza el estado visual del sidebar.
- Consecuencia practica: cuando se crea una nueva seccion navegable con `data-nav`, el controlador debe soportar peticiones AJAX devolviendo solo la seccion `content`, y el frontend probablemente necesita un init global registrable en `initializeScripts`.

## Database Structure Context

- **Nueva arquitectura general:** el sistema ya no debe entenderse como una sola base monolitica. Ahora hay una **base central** para gobernanza multi-sede y una **base operativa por sede** para el inventario real.
- **Base central:** guarda la configuracion global del ecosistema multi-tenant:
  - `tenants`: define cada sede (`name`, `slug`, `database`, `is_active`).
  - `domains`: dominios o subdominios asociados a cada sede.
  - `tenant_branding`: branding visual, logos, colores, textos y timezone por sede.
  - `user_tenant`: membresias usuario-sede y rol dentro de cada sede.
- **Base tenant por sede:** guarda toda la operacion diaria del inventario. Los modelos operativos usan `UsesTenantConnection`, asi que apuntan a la conexion `tenant` resuelta dinamicamente para la sede activa.
- **Ojo al migrar sedes:** `php artisan migrate:tenants` marca como ejecutada cualquier migracion nueva en bases existentes sin correrla. Para aplicar migraciones nuevas usa `php artisan tenant:migrate --all`.

### Core Operational Data Flow

- `groups` representa agrupaciones logicas de inventarios dentro de una sede.
- `inventories` pertenece a un grupo y contiene el responsable y estado de conservacion.
- `assets` es el catalogo global de bienes dentro de la sede. Cada bien tiene un `type`:
  - `Cantidad`: bienes contables por unidades agregadas.
  - `Serial`: bienes rastreados uno a uno.
- `asset_inventory` es la tabla pivote que dice en que inventarios existe cada bien. La relacion `asset_id + inventory_id` es unica.
- A partir de esa pivote, el flujo diverge segun el tipo de bien:
  - `asset_quantities`: almacena la cantidad acumulada cuando el bien es de tipo `Cantidad`.
  - `asset_equipments`: almacena cada equipo individual cuando el bien es de tipo `Serial`, con serial unico y metadatos tecnicos.
- Esto significa que el sistema trabaja con **un solo catalogo de bienes por sede**, pero dos formas de persistir existencias:
  - bienes agregados por cantidad;
  - bienes unitarios por serial.

### Removed Assets Flow

- Las bajas ya no son solo una resta de existencias; tambien generan historial persistente.
- `assets_removed` guarda bajas de bienes tipo `Cantidad` con cantidad removida, motivo, usuario, bien original e inventario origen.
- `asset_equipments_removed` guarda bajas de bienes tipo `Serial` preservando los datos tecnicos del equipo removido: serial, marca, modelo, estado, condiciones, fechas y motivo.
- En consecuencia, el flujo correcto de baja es:
  1. localizar el bien dentro del inventario;
  2. mover evidencia al historial de bajas correspondiente;
  3. actualizar o eliminar el registro activo en inventario.

### Reporting and Read Models

- El proyecto usa vistas SQL para simplificar consultas repetidas y acelerar pantallas/reportes:
  - `assets_summary_view`: resume el total global por bien en la sede.
  - `inventory_goods_view`: resume cuantos bienes de cada tipo hay en cada inventario.
  - `serial_goods_view`: expone el detalle plano de cada equipo serializado.
- En la practica, varias pantallas y controladores leen desde estas vistas en lugar de recomponer joins complejos en cada request.
- `activity_logs` actua como capa de auditoria transversal y conserva accion, modelo afectado, descripcion, IP, agente de usuario y snapshots `old_values/new_values`.
- `report_folders` y `reports` separan la organizacion logica de reportes de los archivos PDF generados en storage.

## Multi-Sede And Portal Context

- **Division por sedes:** cada sede tiene su propia base de datos operativa. Eso aisla inventarios, bienes, bajas, reportes y actividad de una sede frente a otra.
- **Portal central:** el portal no reemplaza la operacion de sede; la engloba y la orquesta. Su responsabilidad es listar sedes accesibles, resolver a cual puede entrar el usuario y mostrar un resumen consolidado.
- `PortalController` consulta la base central para saber que sedes existen y a cuales tiene acceso el usuario. Luego abre conexiones temporales a la base de cada sede para calcular estadisticas e inventarios visibles desde el portal.
- `TenantResolver` decide si el request pertenece al portal central o a una sede concreta usando estrategia por subdominio, dominio completo o sesion.
- `TenantContext` configura la conexion `tenant` en tiempo de request y ademas la deja como conexion por defecto, por lo que `DB::table()` y modelos operativos trabajan automaticamente contra la base de la sede activa.
- Flujo de alto nivel:
  1. el usuario entra al portal central o a un dominio de sede;
  2. el resolver identifica la sede activa o determina que sigue en portal central;
  3. si hay sede activa, la conexion `tenant` apunta a esa base;
  4. todos los controladores operativos leen y escriben solo en los datos de esa sede;
  5. el portal puede cambiar de sede guardando `tenant_id` en sesion y redirigiendo al dominio principal de esa sede.
- Consecuencia practica para desarrollo:
  - cambios en inventario, bienes, bajas, reportes y logs suelen pertenecer a la base tenant;
  - cambios en acceso multi-sede, branding, dominios o seleccion de sede pertenecen a la base central;
  - cuando se agregan modelos o consultas operativas nuevas, normalmente deben usar la conexion tenant, no la central.

## Git Branches

- `master` - Main/production branch (use for PRs)
- `develop` - Active development branch
