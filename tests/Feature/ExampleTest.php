<?php

use App\Models\User;

test('retorna una respuesta exitosa', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get(route('home.index'));

    $response->assertStatus(200);
});