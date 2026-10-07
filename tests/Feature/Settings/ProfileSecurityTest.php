<?php

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;

test('password can be updated with the current password', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('current_password', 'password')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('password update rejects a wrong current password', function () {
    $user = User::factory()->create(['password' => 'password']);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('current_password', 'salah')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasErrors(['current_password']);

    expect(Hash::check('password', $user->refresh()->password))->toBeTrue();
});

test('password can be set when the account has none yet', function () {
    $user = User::factory()->create(['password' => null]);

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('password', 'new-password')
        ->set('password_confirmation', 'new-password')
        ->call('updatePassword')
        ->assertHasNoErrors();

    expect(Hash::check('new-password', $user->refresh()->password))->toBeTrue();
});

test('avatar can be uploaded', function () {
    Storage::fake('public');

    $user = User::factory()->create();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->set('name', $user->name)
        ->set('photo', UploadedFile::fake()->image('avatar.jpg'))
        ->call('updateProfileInformation')
        ->assertHasNoErrors();

    $avatar = $user->refresh()->avatar;

    expect($avatar)->not->toBe('')
        ->and(Storage::disk('public')->exists($avatar))->toBeTrue();
});

test('avatar can be removed', function () {
    Storage::fake('public');

    $user = User::factory()->create();
    $path = UploadedFile::fake()->image('avatar.jpg')->store('avatars', 'public');
    $user->forceFill(['avatar' => $path])->save();

    $this->actingAs($user);

    Livewire::test('pages::settings.profile')
        ->call('removePhoto')
        ->assertHasNoErrors();

    expect($user->refresh()->avatar)->toBe('')
        ->and(Storage::disk('public')->exists($path))->toBeFalse();
});
