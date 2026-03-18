<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;

describe('Vista Mi perfil', function () {
    it('un administrador autenticado puede acceder a /profile', function () {
        $this->actingAs(adminUser())
            ->get('/profile')
            ->assertStatus(200)
            ->assertSee('Mi perfil')
            ->assertSee('Informacion de la cuenta')
            ->assertSee('formEditarPerfil')
            ->assertSee('formCambiarPassword');
    });

    it('un consultor autenticado puede acceder a /profile', function () {
        $consultor = consultorUser();

        $this->actingAs($consultor)
            ->get('/profile')
            ->assertStatus(200)
            ->assertSee($consultor->name)
            ->assertSee($consultor->email)
            ->assertSee('@' . $consultor->username);
    });

    it('un usuario no autenticado es redirigido al login', function () {
        $this->get('/profile')
            ->assertRedirect('/login');
    });
});

describe('Actualizar Mi perfil', function () {
    it('un usuario puede actualizar su nombre, username y email', function () {
        $user = consultorUser();

        $this->actingAs($user)
            ->postJson('/api/profile/update', [
                'name' => 'Perfil Actualizado',
                'username' => 'perfil_actualizado',
                'email' => 'perfil.actualizado@uniguajira.edu.co',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Perfil actualizado correctamente.',
            ]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Perfil Actualizado',
            'username' => 'perfil_actualizado',
            'email' => 'perfil.actualizado@uniguajira.edu.co',
        ]);
    });

    it('permite conservar el mismo email y username del usuario autenticado', function () {
        $user = adminUser();

        $this->actingAs($user)
            ->postJson('/api/profile/update', [
                'name' => 'Administrador Editado',
                'username' => $user->username,
                'email' => $user->email,
            ])
            ->assertStatus(200)
            ->assertJson(['success' => true]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Administrador Editado',
            'username' => $user->username,
            'email' => $user->email,
        ]);
    });

    it('no permite usar el email de otro usuario', function () {
        $user = consultorUser();
        $otherUser = User::factory()->administrador()->create([
            'email' => 'ocupado@uniguajira.edu.co',
        ]);

        $this->actingAs($user)
            ->postJson('/api/profile/update', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => $otherUser->email,
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => $user->email,
        ]);
    });

    it('si el email cambia, se marca como pendiente de verificacion', function () {
        $user = User::factory()->consultor()->create([
            'email_verified_at' => now(),
        ]);

        $this->actingAs($user)
            ->postJson('/api/profile/update', [
                'name' => $user->name,
                'username' => $user->username,
                'email' => 'nuevo.correo@uniguajira.edu.co',
            ])
            ->assertStatus(200);

        expect($user->fresh()->email_verified_at)->toBeNull();
    });

    it('un usuario no autenticado no puede actualizar el perfil', function () {
        $this->postJson('/api/profile/update', [
            'name' => 'Sin Auth',
            'username' => 'sin_auth',
            'email' => 'sin-auth@uniguajira.edu.co',
        ])->assertStatus(401);
    });

    it('un usuario puede actualizar su contrasena con la contrasena actual correcta', function () {
        $user = consultorUser();

        $this->actingAs($user)
            ->postJson('/api/profile/password', [
                'current_password' => 'password',
                'password' => 'NuevaClaveSegura123!',
                'password_confirmation' => 'NuevaClaveSegura123!',
            ])
            ->assertStatus(200)
            ->assertJson([
                'success' => true,
                'message' => 'Contrasena actualizada correctamente.',
            ]);

        expect(Hash::check('NuevaClaveSegura123!', $user->fresh()->password))->toBeTrue();
    });

    it('no actualiza la contrasena si la contrasena actual es incorrecta', function () {
        $user = adminUser();
        $hashOriginal = $user->password;

        $this->actingAs($user)
            ->postJson('/api/profile/password', [
                'current_password' => 'incorrecta',
                'password' => 'OtraClaveSegura123!',
                'password_confirmation' => 'OtraClaveSegura123!',
            ])
            ->assertStatus(422)
            ->assertJson(['success' => false]);

        expect($user->fresh()->password)->toBe($hashOriginal);
    });
});
