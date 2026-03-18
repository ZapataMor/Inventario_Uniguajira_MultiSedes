# TEST.md - Guia de pruebas · Inventario Uniguajira

Guia de referencia para escribir, organizar y ejecutar los tests del proyecto.

Framework: PEST PHP 4
Base: Laravel 12
DB de pruebas: SQLite en memoria con `RefreshDatabase`

---

## Indice

1. [Comandos rapidos](#1-comandos-rapidos)
2. [Estructura de carpetas](#2-estructura-de-carpetas)
3. [Roles y permisos](#3-roles-y-permisos)
4. [Helpers globales](#4-helpers-globales)
5. [Convenciones de escritura](#5-convenciones-de-escritura)
6. [Tipos de tests por modulo](#6-tipos-de-tests-por-modulo)
7. [Que testear por rol](#7-que-testear-por-rol)
8. [Reglas de validacion comunes](#8-reglas-de-validacion-comunes)
9. [Como agregar un modulo nuevo](#9-como-agregar-un-modulo-nuevo)
10. [Test transversal de rutas web](#10-test-transversal-de-rutas-web)

---

## 1. Comandos rapidos

```bash
# Correr toda la suite
./vendor/bin/pest

# Correr solo Feature
./vendor/bin/pest tests/Feature/

# Correr solo Unit
./vendor/bin/pest tests/Unit/

# Correr un modulo especifico
./vendor/bin/pest tests/Feature/Tasks/

# Correr un archivo puntual
./vendor/bin/pest tests/Feature/Users/Admin/UserCrudTest.php

# Filtrar por nombre del test
./vendor/bin/pest --filter "puede crear una tarea"

# Salida detallada
./vendor/bin/pest --verbose

# Paralelo
./vendor/bin/pest --parallel

# Cobertura
./vendor/bin/pest --coverage
```

---

## 2. Estructura de carpetas

Los tests se organizan por modulo y, dentro de cada modulo, por rol o tipo de comportamiento.

```text
tests/
|-- Pest.php
|-- TestCase.php
|-- TEST.md
|
|-- Feature/
|   |-- Auth/
|   |-- Goods/
|   |-- Home/
|   |-- Tasks/
|   |-- Users/
|   |-- WebRoutesPerformanceTest.php
|
`-- Unit/
```

### Convencion de nombres

| Tipo | Sufijo | Ejemplo |
|---|---|---|
| Vista | `ViewTest.php` | `AdminHomeViewTest.php` |
| CRUD | `CrudTest.php` | `TaskCrudTest.php` |
| Validacion | `ValidationTest.php` | `TaskValidationTest.php` |
| Acceso | `AccessTest.php` | `TaskAccessTest.php` |
| Rendimiento transversal | `PerformanceTest.php` | `WebRoutesPerformanceTest.php` |

---

## 3. Roles y permisos

El sistema usa dos roles en `users.role`:

| Rol | Valor en BD | Alcance esperado |
|---|---|---|
| Administrador | `administrador` | Gestion completa de modulos |
| Consultor | `consultor` | Solo lectura de vistas permitidas |

### Comportamiento esperado

Administrador:
- accede a vistas de gestion
- puede crear, editar y eliminar registros
- ve botones, modales y acciones administrativas

Consultor:
- accede solo a las vistas permitidas
- no ve controles de gestion
- no debe ejecutar acciones administrativas

No autenticado:
- es redirigido a login en vistas protegidas
- recibe `401` o redireccion en endpoints segun el middleware aplicado

---

## 4. Helpers globales

Los helpers compartidos viven en `tests/Pest.php`.

### Usuarios

```php
adminUser(): User
consultorUser(): User
```

Tambien se pueden usar estados de factory:

```php
User::factory()->administrador()->create();
User::factory()->consultor()->create();
```

### Tareas

```php
crearTareaPendiente(User $user, array $overrides = []): Task
crearTareaCompletada(User $user, array $overrides = []): Task
```

### Bienes

```php
crearBien(array $overrides = []): Asset
crearBienConCantidad(int $cantidad = 5, array $overrides = []): Asset
crearBienConSerial(array $overrides = []): Asset
```

### Cuando agregar un helper nuevo

Conviene agregar helpers cuando:

- el mismo setup aparece en varios archivos
- el modelo tiene relaciones minimas obligatorias
- el flujo de prueba requiere datos coherentes y repetibles

Ejemplo:

```php
function crearInventario(array $overrides = []): Inventory
{
    $group = Group::create(['name' => 'Grupo de prueba']);

    return Inventory::create(array_merge([
        'name' => 'Inventario de prueba',
        'responsible' => 'Usuario demo',
        'conservation_status' => 'good',
        'group_id' => $group->id,
    ], $overrides));
}
```

---

## 5. Convenciones de escritura

### Sintaxis

La suite usa PEST. No se deben crear clases PHPUnit para tests nuevos salvo que haya una razon fuerte y documentada.

```php
describe('CRUD de Tareas - Administrador', function () {
    describe('Crear tarea', function () {
        it('puede crear una tarea con datos validos', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('tasks.store'), [
                    'name' => 'Nueva tarea',
                    'date' => now()->addDays(3)->toDateString(),
                ])
                ->assertStatus(200);
        });
    });
});
```

### Titulos

Los titulos deben:

- estar en espanol
- describir comportamiento esperado
- permitir entender el fallo sin leer el cuerpo

Buenos ejemplos:

- `it('no puede crear una tarea con fecha anterior a hoy')`
- `it('el consultor no ve el boton de eliminar tarea')`
- `it('retorna 404 al editar un registro inexistente')`

Malos ejemplos:

- `it('test fecha')`
- `it('funciona bien')`
- `it('valida')`

### Estructura recomendada

1. Arrange
2. Act
3. Assert

---

## 6. Tipos de tests por modulo

### `*CrudTest.php`

Valida operaciones exitosas y fallos basicos del recurso:

- crear
- actualizar
- eliminar
- respuesta `404` cuando el registro no existe

### `*ValidationTest.php`

Valida reglas de negocio y entradas invalidas:

- campos requeridos
- longitud maxima
- formato incorrecto
- mensajes y estructura `422`

### `*ViewTest.php`

Valida lo que el usuario realmente ve:

- titulos
- tablas
- botones
- modales
- estados vacios

### `*AccessTest.php`

Valida autorizacion:

- no autenticado
- consultor
- restricciones de UI
- restricciones de endpoints

### `*PerformanceTest.php`

Valida tiempos de respuesta y disponibilidad transversal:

- rutas que deben responder sin error
- rutas con parametros dinamicos
- umbrales maximos de tiempo
- exclusiones controladas

---

## 7. Que testear por rol

### Administrador

| Escenario | Verificacion |
|---|---|
| Acceso a vista | `200` |
| Ve interfaz de gestion | `assertSee(...)` |
| CRUD exitoso | `assertStatus(200)` + BD |
| Validaciones | `422` + `assertJsonValidationErrors` |
| Recurso inexistente | `404` |

### Consultor

| Escenario | Verificacion |
|---|---|
| Acceso a vistas permitidas | `200` |
| No ve gestion | `assertDontSee(...)` |
| No ejecuta acciones admin | `403`, `401` o ausencia de UI segun aplique |

### No autenticado

| Escenario | Verificacion |
|---|---|
| Vista protegida | redireccion a `/login` |
| API protegida | `401` |

---

## 8. Reglas de validacion comunes

### Campo requerido

```php
it('el nombre es obligatorio', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
```

### Longitud maxima

```php
it('el nombre no puede superar 255 caracteres', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), [
            'name' => str_repeat('A', 256),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
```

### Fecha no valida o pasada

```php
it('la fecha no puede ser anterior a hoy', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), [
            'name' => 'Test',
            'date' => now()->subDay()->toDateString(),
        ])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['date']);
});
```

### Campo nullable

```php
it('la descripcion es opcional', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), [
            'name' => 'Sin descripcion',
            'date' => now()->addDays(2)->toDateString(),
        ])
        ->assertStatus(200);
});
```

---

## 9. Como agregar un modulo nuevo

Ejemplo con `Goods`.

### 1. Crear carpetas

```text
tests/Feature/Goods/
|-- Admin/
|   |-- GoodsCrudTest.php
|   `-- GoodsValidationTest.php
`-- Consultor/
    `-- GoodsAccessTest.php
```

### 2. Agregar helpers si hace falta

Evitar repetir setup complejo dentro de cada test.

### 3. Empezar por cuatro frentes

- vista admin
- CRUD admin
- validaciones admin
- acceso consultor y no autenticado

### 4. Correr solo ese modulo

```bash
./vendor/bin/pest tests/Feature/Goods/
```

---

## 10. Test transversal de rutas web

Archivo agregado:

```text
tests/Feature/WebRoutesPerformanceTest.php
```

### Objetivo

Este test recorre las rutas `GET` web mas relevantes del proyecto y valida dos cosas:

1. que respondan sin error funcional
2. que lo hagan dentro de un tiempo razonable

La asercion acepta:

- `200`
- redirecciones validas
- `204`

### Por que existe

Este test sirve como red de seguridad transversal. No reemplaza los tests por modulo, pero ayuda a detectar rapido:

- rutas rotas por cambios de controladores o vistas
- joins o vistas SQL que fallan por falta de datos
- regresiones de rendimiento evidentes
- rutas con parametros que dejaron de resolverse

### Datos minimos que prepara

Antes de medir rutas, el test crea un contexto pequeno pero realista:

- un usuario administrador autenticado
- tareas pendientes y completadas para `/home`
- un `Group` y un `Inventory`
- un bien de tipo `Cantidad` con registro en `asset_quantities`
- un bien de tipo `Serial` con registro en `asset_equipments`
- un registro en `assets_removed` para `/removed/{id}`
- una carpeta y un reporte para `/reports/folder/{folderId}`
- un `ActivityLog` para `/records`

Esto permite que rutas como:

- `/group/{groupId}`
- `/group/{groupId}/inventory/{inventoryId}`
- `/group/{groupId}/inventory/{inventoryId}/goods/{assetId}/serials`
- `/removed/{id}`
- `/reports/folder/{folderId}`

puedan resolverse con datos consistentes.

### Como resuelve parametros dinamicos

El test construye URLs reales para placeholders frecuentes:

- `groupId`
- `inventoryId`
- `assetId`
- `folderId`
- `id`
- `path`
- `token`

Si aparece una ruta con un parametro obligatorio que todavia no tiene valor mapeado, esa ruta se omite para evitar falsos negativos. Cuando se agregue una ruta nueva con parametros, hay que extender el mapa de valores del test.

### Rutas excluidas

No todo `GET` registrado conviene medir en esta prueba. Se excluyen:

- `api/*`
- `flux/*`
- `livewire/*`
- `storage/*`
- `user/two-factor-*`
- `user/confirmed-password-status`
- rutas con middleware `signed`
- `register`, porque la aplicacion la deshabilita intencionalmente con `404`

Motivo: varias de esas rutas pertenecen al framework, requieren contexto especial o no representan pantallas funcionales del sistema.

### Estrategia de medicion

Cada ruta se llama dos veces:

1. una peticion de calentamiento
2. una segunda peticion que es la que se mide

Con eso se reduce el ruido inicial de bootstrapping y la medicion queda mas estable.

### Umbrales actuales

```php
DEFAULT_MAX_MS = 500
HEAVY_MAX_MS = 1500
```

Regla aplicada:

- rutas normales: `500 ms`
- rutas que contienen `download` o `export`: `1500 ms`

Si una ruta nueva supera el limite, primero revisar consultas, vistas o carga de datos. Subir el umbral debe ser la ultima opcion y siempre conviene documentar el motivo.

### Como ejecutarlo

```bash
./vendor/bin/pest tests/Feature/WebRoutesPerformanceTest.php
```

### Nota de implementacion

El ejemplo base compartido para este trabajo estaba escrito como clase PHPUnit. En este proyecto se adapto a PEST para mantener coherencia con la suite actual, reutilizar los helpers globales y seguir la convencion de `tests/Pest.php`.

---

## Estado actual de la suite

| Modulo | Cobertura principal | Estado |
|---|---|---|
| Auth | login, logout, rutas publicas bloqueadas | OK |
| Home | vista admin y consultor | OK |
| Tasks | CRUD, validaciones, acceso | OK |
| Users | vista, CRUD, validaciones, acceso | OK |
| Goods | CRUD, vista, validaciones, carga Excel | OK |
| Web routes | smoke test + rendimiento GET | OK |

Actualizar esta tabla cuando se agregue un modulo o un nuevo tipo de prueba transversal.
