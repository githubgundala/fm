<?php

use App\Models\User;

test('guests can view the login page', function () {
    $this->get('/login')->assertOk();
});

test('a user can log in with email and password', function () {
    $user = User::factory()->create([
        'email' => 'web@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'web@example.com',
        'password' => 'password',
    ])->assertRedirect('/dashboard');

    $this->assertAuthenticatedAs($user);
});

test('web login rejects a wrong password', function () {
    User::factory()->create([
        'email' => 'web@example.com',
        'password' => 'password',
    ]);

    $this->post('/login', [
        'email' => 'web@example.com',
        'password' => 'salah',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('an authenticated user can log out', function () {
    $this->actingAs(User::factory()->create());

    $this->post('/logout')->assertRedirect('/');

    $this->assertGuest();
});
