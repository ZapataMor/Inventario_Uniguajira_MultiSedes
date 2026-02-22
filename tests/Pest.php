<?php

use App\Models\User;
use App\Models\Task;

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

pest()->extend(Tests\TestCase::class)
    ->use(Illuminate\Foundation\Testing\RefreshDatabase::class)
    ->in('Feature');

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return $this->toBe(1);
});

/*
|--------------------------------------------------------------------------
| Helpers globales de usuarios
|--------------------------------------------------------------------------
*/

/**
 * Crea y retorna un usuario con rol 'administrador'.
 */
function adminUser(): User
{
    return User::factory()->administrador()->create();
}

/**
 * Crea y retorna un usuario con rol 'consultor'.
 */
function consultorUser(): User
{
    return User::factory()->consultor()->create();
}

/*
|--------------------------------------------------------------------------
| Helpers globales de tareas
|--------------------------------------------------------------------------
*/

/**
 * Crea una tarea pendiente asociada al usuario dado.
 *
 * @param  array<string, mixed>  $overrides  Atributos opcionales para sobreescribir.
 */
function crearTareaPendiente(User $user, array $overrides = []): Task
{
    return Task::create(array_merge([
        'name'        => 'Tarea de prueba',
        'description' => 'Descripción de prueba',
        'date'        => now()->addDays(5)->toDateString(),
        'status'      => 'pending',
        'user_id'     => $user->id,
    ], $overrides));
}

/**
 * Crea una tarea completada asociada al usuario dado.
 *
 * @param  array<string, mixed>  $overrides  Atributos opcionales para sobreescribir.
 */
function crearTareaCompletada(User $user, array $overrides = []): Task
{
    return Task::create(array_merge([
        'name'        => 'Tarea completada',
        'description' => 'Descripción completada',
        'date'        => now()->addDays(5)->toDateString(),
        'status'      => 'completed',
        'user_id'     => $user->id,
    ], $overrides));
}
