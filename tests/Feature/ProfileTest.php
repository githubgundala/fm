<?php

use App\Filament\Auth\Pages\EditProfile;
use App\Models\User;

use function Pest\Livewire\livewire;

test('guests cannot visit the profile page', function () {
    $this->get('/admin/profile')->assertRedirect('/admin/login');
});

test('authenticated users can visit their profile page', function () {
    $this->actingAs(User::factory()->create());

    $this->get('/admin/profile')->assertOk();
});

test('users can update their profile name', function () {
    $user = User::factory()->create();
    $this->actingAs($user);

    livewire(EditProfile::class)
        ->fillForm([
            'name' => 'Nama Baru',
        ])
        ->call('save')
        ->assertHasNoFormErrors()
        ->assertNotified();

    expect($user->fresh()->name)->toBe('Nama Baru');
});
