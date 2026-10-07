<?php

use App\Models\User;
use Filament\Auth\Pages\Login;

use function Pest\Livewire\livewire;

test('a user can log in to the admin panel with email and password', function () {
    $user = User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@example.com',
            'password' => 'password',
        ])
        ->call('authenticate')
        ->assertHasNoFormErrors()
        ->assertRedirect('/admin');

    $this->assertAuthenticatedAs($user);
});

test('admin panel login rejects a wrong password', function () {
    User::factory()->create([
        'email' => 'admin@example.com',
        'password' => 'password',
    ]);

    livewire(Login::class)
        ->fillForm([
            'email' => 'admin@example.com',
            'password' => 'salah',
        ])
        ->call('authenticate')
        ->assertHasFormErrors(['email']);

    $this->assertGuest();
});
