<?php

use App\Models\User;
use Laravel\Fortify\Features;

test('la pantalla de login puede ser renderizada', function () {
    $response = $this->get(route('login'));

    $response->assertStatus(200);
});

test('los usuarios pueden autenticarse usando la pantalla de login', function () {
    $user = User::factory()->withoutTwoFactor()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('home.index', absolute: false));

    $this->assertAuthenticated();
});

test('los usuarios no pueden autenticarse con contraseña inválida', function () {
    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'wrong-password',
    ]);

    $response->assertSessionHasErrorsIn('email');

    $this->assertGuest();
});

test('los usuarios con autenticación de dos factores habilitada son redirigidos al desafío de dos factores', function () {
    if (! Features::canManageTwoFactorAuthentication()) {
        $this->markTestSkipped('Two-factor authentication is not enabled.');
    }

    Features::twoFactorAuthentication([
        'confirm' => true,
        'confirmPassword' => true,
    ]);

    $user = User::factory()->create();

    $response = $this->post(route('login.store'), [
        'email' => $user->email,
        'password' => 'password',
    ]);

    $response->assertRedirect(route('two-factor.login'));
    $this->assertGuest();
});

test('los usuarios pueden cerrar sesión', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->post(route('logout'));

    $response->assertRedirect('/');

    $this->assertGuest();
});

// since registration is disabled for this private system verify public routes are gone
test('la ruta de registro pública no está disponible', function () {
    $this->get('/register')->assertStatus(404);
    $this->post('/register', [
        'name' => 'foo',
        'email' => 'foo@example.com',
        'password' => 'password',
        'password_confirmation' => 'password',
    ])->assertStatus(404);
});
