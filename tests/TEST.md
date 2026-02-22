# TEST.md — Guía de pruebas · Inventario Uniguajira

Guía de referencia para escribir, organizar y ejecutar los tests del proyecto.
**Framework:** PEST PHP 4 · **Base:** Laravel 12 · **DB de pruebas:** SQLite en memoria (RefreshDatabase)

---

## Índice

1. [Comandos rápidos](#1-comandos-rápidos)
2. [Estructura de carpetas](#2-estructura-de-carpetas)
3. [Roles y permisos](#3-roles-y-permisos)
4. [Helpers globales (Pest.php)](#4-helpers-globales-pestphp)
5. [Convenciones de escritura](#5-convenciones-de-escritura)
6. [Tipos de tests por módulo](#6-tipos-de-tests-por-módulo)
7. [Qué testear en cada rol](#7-qué-testear-en-cada-rol)
8. [Reglas de validación comunes](#8-reglas-de-validación-comunes)
9. [Añadir un nuevo módulo](#9-añadir-un-nuevo-módulo)

---

## 1. Comandos rápidos

```bash
# Correr TODA la suite
./vendor/bin/pest

# Correr solo los tests de Feature
./vendor/bin/pest tests/Feature/

# Correr un módulo específico
./vendor/bin/pest tests/Feature/Tasks/

# Correr un archivo específico
./vendor/bin/pest tests/Feature/Tasks/Admin/TaskCrudTest.php

# Correr un test por nombre (busca el string en el título)
./vendor/bin/pest --filter "puede crear una tarea"

# Ver output detallado (útil para depurar)
./vendor/bin/pest --verbose

# Correr en paralelo (más rápido)
./vendor/bin/pest --parallel

# Ver cobertura de código
./vendor/bin/pest --coverage
```

---

## 2. Estructura de carpetas

Los tests se organizan **por módulo** y dentro de cada módulo **por rol**.

```
tests/
├── Pest.php                        ← Configuración global + helpers reutilizables
├── TestCase.php                    ← Clase base (extiende Laravel TestCase)
├── TEST.md                         ← Esta guía
│
├── Feature/                        ← Tests de integración (HTTP, BD, vistas)
│   ├── Auth/
│   │   └── AuthenticationTest.php
│   │
│   ├── Home/                       ← Módulo: vista principal
│   │   ├── Admin/
│   │   │   └── AdminHomeViewTest.php
│   │   └── Consultor/
│   │       └── ConsultorHomeViewTest.php
│   │
│   ├── Tasks/                      ← Módulo: tareas
│   │   ├── Admin/
│   │   │   ├── TaskCrudTest.php
│   │   │   └── TaskValidationTest.php
│   │   └── Consultor/
│   │       └── TaskAccessTest.php
│   │
│   └── {Modulo}/                   ← Patrón para futuros módulos
│       ├── Admin/
│       │   ├── {Modulo}CrudTest.php
│       │   └── {Modulo}ValidationTest.php
│       └── Consultor/
│           └── {Modulo}AccessTest.php
│
└── Unit/                           ← Tests unitarios puros (sin BD, sin HTTP)
    └── ExampleTest.php
```

### Convención de nombres de archivo

| Tipo de test | Sufijo | Ejemplo |
|---|---|---|
| Vista / UI | `ViewTest.php` | `AdminHomeViewTest.php` |
| CRUD completo | `CrudTest.php` | `TaskCrudTest.php` |
| Validaciones | `ValidationTest.php` | `TaskValidationTest.php` |
| Control de acceso | `AccessTest.php` | `TaskAccessTest.php` |

---

## 3. Roles y permisos

El sistema tiene dos roles definidos en la columna `users.role` (enum):

| Rol | Valor en BD | Puede hacer |
|---|---|---|
| **Administrador** | `'administrador'` | CRUD completo de todos los módulos |
| **Consultor** | `'consultor'` | Solo lectura de las vistas permitidas; no ve módulos de gestión |

### Comportamiento esperado por rol

**Administrador:**
- Accede a `/home` y ve el panel completo de tareas
- Puede crear, editar, marcar como completada y eliminar tareas
- Ve la interfaz de gestión (botones, formularios, modales)

**Consultor:**
- Accede a `/home` y ve únicamente el mensaje de bienvenida e información de consultor
- **No ve** ningún elemento del módulo de tareas (botones, listas, formularios)
- Recibe `401` en todos los endpoints de la API si no está autenticado
- No tiene restricción de rol en la API (solo `auth` middleware); la restricción es visual

---

## 4. Helpers globales (`Pest.php`)

Todos los helpers están disponibles en cualquier test de `Feature/` sin importar nada.

### Usuarios

```php
// Crea un usuario con rol 'administrador'
adminUser(): User

// Crea un usuario con rol 'consultor'
consultorUser(): User
```

También se pueden usar los estados de la factory directamente:

```php
User::factory()->administrador()->create();
User::factory()->consultor()->create(['name' => 'Juan Pérez']);
```

### Tareas

```php
// Crea una tarea pendiente (date: +5 días por defecto)
crearTareaPendiente(User $user, array $overrides = []): Task

// Crea una tarea completada (date: +5 días por defecto)
crearTareaCompletada(User $user, array $overrides = []): Task
```

**Ejemplo de uso con overrides:**

```php
// Tarea vencida (insertada directo en BD para saltarse validación de API)
Task::create([
    'name'    => 'Tarea vencida',
    'date'    => now()->subDays(2)->toDateString(),
    'status'  => 'pending',
    'user_id' => $admin->id,
]);

// Tarea con fecha específica
crearTareaPendiente($admin, [
    'name' => 'Tarea urgente',
    'date' => now()->addDay()->toDateString(),
]);
```

### Añadir nuevos helpers

Agregar en `tests/Pest.php` siguiendo el mismo patrón:

```php
function crearInventario(User $user, array $overrides = []): Inventory
{
    return Inventory::create(array_merge([
        'name'    => 'Inventario de prueba',
        'user_id' => $user->id,
    ], $overrides));
}
```

---

## 5. Convenciones de escritura

### Sintaxis PEST (obligatoria)

Todos los tests usan la sintaxis funcional de PEST, **no** clases PHPUnit.

```php
<?php

// ✅ Correcto — PEST funcional con describe anidado
describe('Módulo X - Rol Y', function () {

    describe('Acción específica', function () {

        it('descripción en español del comportamiento esperado', function () {
            $admin = adminUser();

            $this->actingAs($admin)
                ->postJson(route('ruta.nombre'), [...])
                ->assertStatus(200)
                ->assertJson(['success' => true]);
        });
    });
});

// ❌ Incorrecto — No usar clases PHPUnit
class MiTest extends TestCase {
    public function test_algo() { ... }
}
```

### Títulos de tests

- Escritos en **español**, en tercera persona o infinitivo
- Describen el **comportamiento esperado**, no el código que se ejecuta
- Suficientemente específicos para entender el fallo sin leer el cuerpo

```php
// ✅ Buenos títulos
it('no puede crear una tarea con fecha anterior a hoy')
it('el consultor no ve el botón de eliminar tarea')
it('retorna 404 al intentar editar una tarea inexistente')

// ❌ Malos títulos
it('test fecha')
it('valida tarea')
it('funciona bien')
```

### Estructura interna de un test

```php
it('puede crear una tarea con todos los campos', function () {
    // 1. ARRANGE — preparar datos
    $admin = adminUser();

    // 2. ACT — ejecutar la acción
    $response = $this->actingAs($admin)
        ->postJson(route('tasks.store'), [
            'name' => 'Nueva tarea',
            'date' => now()->addDays(3)->toDateString(),
        ]);

    // 3. ASSERT — verificar resultado
    $response->assertStatus(200)->assertJson(['success' => true]);

    $this->assertDatabaseHas('tasks', [
        'name'   => 'Nueva tarea',
        'status' => 'pending',
    ]);
});
```

### `describe` anidado

Usar dos niveles:
1. Nivel 1 → `Módulo - Rol` (contexto general)
2. Nivel 2 → Acción o grupo de validación

```php
describe('CRUD de Tareas - Administrador', function () {
    describe('Crear tarea', function () {
        it('puede crear con todos los campos', function () { ... });
        it('puede crear sin descripción', function () { ... });
    });
    describe('Eliminar tarea', function () {
        it('puede eliminar una tarea pendiente', function () { ... });
        it('retorna 404 si no existe', function () { ... });
    });
});
```

---

## 6. Tipos de tests por módulo

Cada módulo debe cubrir estos cuatro tipos de archivos:

### `{Modulo}CrudTest.php` — Operaciones de datos (Admin)

Prueba que el administrador pueda ejecutar cada operación exitosamente:

- **Crear** → `POST /api/{modulo}/store` → `assertStatus(200)` + `assertDatabaseHas`
- **Editar** → `PUT /api/{modulo}/update` → `assertStatus(200)` + `assertDatabaseHas`
- **Eliminar** → `DELETE /api/{modulo}/delete/{id}` → `assertStatus(200)` + `assertDatabaseMissing`
- **Casos 404** → id inexistente devuelve `assertStatus(404)`

### `{Modulo}ValidationTest.php` — Reglas de validación (Admin)

Prueba que la API rechace datos incorrectos con `422` y `assertJsonValidationErrors`:

- Campos requeridos vacíos
- Longitudes máximas superadas (nombre max:255)
- Formatos inválidos (fecha no válida)
- Reglas de negocio (fecha no puede ser pasada)
- Mensajes de error en español

### `Admin{Modulo}ViewTest.php` — Vista del administrador

Prueba lo que el admin **ve** en la vista HTML:

- Secciones y títulos presentes (`assertSee`)
- Botones de acción presentes (`assertSee('css-class-o-texto')`)
- Contenido de registros en la lista
- Estado vacío (mensajes cuando no hay registros)
- Elementos que **no** debe ver (`assertDontSee`)

### `{Modulo}AccessTest.php` — Control de acceso (Consultor / No autenticado)

Prueba que usuarios sin permisos no puedan:

- **No autenticado:** `401` en endpoints de API, redirección a `/login` en vistas
- **Consultor:** no ve elementos de gestión en la vista (`assertDontSee`)
- **Consultor:** no ve registros aunque existan en BD

---

## 7. Qué testear en cada rol

### Administrador ✅

| Escenario | Verificar |
|---|---|
| Acceso a vista | `assertStatus(200)` |
| Ve interfaz de gestión | `assertSee('botón/sección')` |
| CRUD exitoso | `assertStatus(200)` + `assertDatabaseHas/Missing` |
| Estado vacío | Mensaje de "sin registros" |
| Errores de validación | `assertStatus(422)` + `assertJsonValidationErrors` |
| Recurso no encontrado | `assertStatus(404)` |

### Consultor 🔒

| Escenario | Verificar |
|---|---|
| Acceso a vista | `assertStatus(200)` |
| Ve bienvenida/info | `assertSee('Información del Consultor')` |
| No ve gestión | `assertDontSee('add-task-button')`, etc. |
| No ve datos de admin | `assertDontSee('nombre del registro')` |

### No autenticado 🚫

| Escenario | Verificar |
|---|---|
| Vista protegida | `assertRedirect('/login')` |
| Endpoint API (auth) | `assertStatus(401)` |

---

## 8. Reglas de validación comunes

Reglas que se repiten en varios módulos y cómo testearlas:

### Campo requerido

```php
it('el nombre es obligatorio', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), ['name' => ''])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});
```

### Longitud máxima (max:255)

```php
it('el nombre no puede superar 255 caracteres', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), ['name' => str_repeat('A', 256)])
        ->assertStatus(422)
        ->assertJsonValidationErrors(['name']);
});

it('acepta exactamente 255 caracteres', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), ['name' => str_repeat('A', 255)])
        ->assertStatus(200);
});
```

### Fecha no puede ser pasada

```php
it('la fecha no puede ser anterior a hoy', function () {
    $response = $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), [
            'name' => 'Test',
            'date' => now()->subDay()->toDateString(),
        ]);

    $response->assertStatus(422)
        ->assertJsonValidationErrors(['date']);

    // Verificar mensaje en español
    $errors = $response->json('errors.date');
    expect($errors)->toContain('La fecha no puede ser anterior al día de hoy.');
});
```

### Comparación de fechas en BD

MySQL guarda `date` como `Y-m-d H:i:s`. Usar el cast del modelo:

```php
// ✅ Correcto
$fechaGuardada = MiModelo::find($id)->date->toDateString();
expect($fechaGuardada)->toBe($fecha);

// ❌ Puede fallar por el timestamp
$this->assertDatabaseHas('tabla', ['date' => '2026-03-01']);
```

### Campo nullable

```php
it('la descripción es opcional', function () {
    $this->actingAs(adminUser())
        ->postJson(route('modulo.store'), [
            'name' => 'Sin descripción',
            'date' => now()->addDays(2)->toDateString(),
            // 'description' omitido intencionalmente
        ])
        ->assertStatus(200);
});
```

---

## 9. Añadir un nuevo módulo

Pasos para añadir tests a un módulo nuevo (ej. `Goods`):

### 1. Crear carpetas

```
tests/Feature/Goods/
├── Admin/
│   ├── GoodsCrudTest.php
│   └── GoodsValidationTest.php
└── Consultor/
    └── GoodsAccessTest.php
```

### 2. Añadir helpers en `Pest.php`

```php
function crearBien(User $user, array $overrides = []): Asset
{
    return Asset::create(array_merge([
        'name'    => 'Bien de prueba',
        'user_id' => $user->id,
    ], $overrides));
}
```

### 3. Esqueleto de `GoodsCrudTest.php`

```php
<?php

describe('CRUD de Bienes - Administrador', function () {

    describe('Crear bien', function () {
        it('puede crear un bien con datos válidos', function () {
            // ...
        });
    });

    describe('Editar bien', function () {
        it('puede actualizar el nombre de un bien', function () {
            // ...
        });
    });

    describe('Eliminar bien', function () {
        it('puede eliminar un bien', function () {
            // ...
        });
        it('retorna 404 si no existe', function () {
            // ...
        });
    });
});
```

### 4. Esqueleto de `GoodsAccessTest.php`

```php
<?php

describe('Acceso a Bienes - No autenticado', function () {
    it('no puede crear un bien (401)', function () { ... });
    it('no puede eliminar un bien (401)', function () { ... });
});

describe('Vista de Bienes - Consultor', function () {
    it('no ve los botones de gestión', function () { ... });
});
```

### 5. Correr solo el nuevo módulo

```bash
./vendor/bin/pest tests/Feature/Goods/
```

---

## Estado actual de la suite

| Módulo | Admin | Consultor | Tests | Estado |
|---|---|---|---|---|
| Auth | Login / Logout | — | ~5 | ✅ |
| Home (vista) | Vista con tareas | Vista sin tareas | 25 | ✅ |
| Tasks (CRUD) | Crear / Editar / Toggle / Eliminar | — | 16 | ✅ |
| Tasks (Validaciones) | Nombre, Fecha, Descripción, Colores | — | 26 | ✅ |
| Tasks (Acceso) | — | Sin UI + No auth 401 | 8 | ✅ |
| Goods | 🔲 pendiente | 🔲 pendiente | — | — |
| Inventories | 🔲 pendiente | 🔲 pendiente | — | — |
| Groups | 🔲 pendiente | 🔲 pendiente | — | — |
| Reports | 🔲 pendiente | 🔲 pendiente | — | — |
| Users | 🔲 pendiente | 🔲 pendiente | — | — |
| Records | 🔲 pendiente | 🔲 pendiente | — | — |
| Removed | 🔲 pendiente | 🔲 pendiente | — | — |
