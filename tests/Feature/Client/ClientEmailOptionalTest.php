<?php

use App\Filament\Resources\Clients\Pages\CreateClient;
use App\Models\Client;

use function Pest\Livewire\livewire;

it('allows creating a client without email', function (): void {
    $this->actingAs(superAdmin());

    livewire(CreateClient::class)
        ->fillForm([
            'name' => 'Klien Tanpa Email',
            'email' => null,
            'is_active' => true,
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect(Client::where('name', 'Klien Tanpa Email')->first()?->email)->toBeNull();
});
