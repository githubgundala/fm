<?php

use Flux\Flux;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Profile settings')] class extends Component {
    use WithFileUploads;

    public string $name = '';

    public string $email = '';

    public $photo = null;

    public string $current_password = '';

    public string $password = '';

    public string $password_confirmation = '';

    /**
     * Mount the component.
     */
    public function mount(): void
    {
        $this->name = Auth::user()->name;
        $this->email = Auth::user()->email;
    }

    /**
     * Update the profile information for the currently authenticated user.
     */
    public function updateProfileInformation(): void
    {
        $user = Auth::user();

        $validated = $this->validate([
            'name' => ['required', 'string', 'max:255'],
            'photo' => ['nullable', 'image', 'max:2048'],
        ]);

        if ($this->photo) {
            if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
                Storage::disk('public')->delete($user->avatar);
            }

            $validated['avatar'] = $this->photo->store('avatars', 'public');
        }

        unset($validated['photo']);

        $user->fill($validated);
        $user->save();

        $this->reset('photo');

        Flux::toast(variant: 'success', text: __('Profile updated.'));
    }

    /**
     * Remove the user's avatar.
     */
    public function removePhoto(): void
    {
        $user = Auth::user();

        if ($user->avatar && Storage::disk('public')->exists($user->avatar)) {
            Storage::disk('public')->delete($user->avatar);
        }

        $user->forceFill(['avatar' => ''])->save();

        Flux::toast(variant: 'success', text: __('Avatar removed.'));
    }

    /**
     * Update the password for the currently authenticated user.
     */
    public function updatePassword(): void
    {
        $user = Auth::user();

        $rules = [
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];

        if ($user->password) {
            $rules = ['current_password' => ['required', 'string', 'current_password']] + $rules;
        }

        $this->validate($rules);

        $user->password = $this->password;
        $user->save();

        $this->reset('current_password', 'password', 'password_confirmation');

        Flux::toast(variant: 'success', text: __('Password updated.'));
    }
}; ?>

<section class="w-full">
    @include('partials.settings-heading')

    <flux:heading level="2" class="sr-only">{{ __('Profile settings') }}</flux:heading>

    <x-pages::settings.layout :heading="__('Profile')" :subheading="__('Update your name and email address')">
        <form wire:submit="updateProfileInformation" class="my-6 w-full space-y-6">
            <div class="flex items-center gap-4">
                <flux:avatar
                    :src="$photo ? $photo->temporaryUrl() : auth()->user()->avatarUrl()"
                    :name="auth()->user()->name"
                    :initials="auth()->user()->initials()"
                    size="xl"
                />

                <div class="grid gap-2">
                    <flux:input wire:model="photo" :label="__('Photo')" type="file" accept="image/*" />

                    @if (auth()->user()->avatarUrl())
                        <flux:button variant="ghost" size="sm" wire:click="removePhoto">{{ __('Remove photo') }}</flux:button>
                    @endif
                </div>
            </div>

            <flux:input wire:model="name" :label="__('Name')" type="text" required autofocus autocomplete="name" />

            <div>
                <flux:input wire:model="email" :label="__('Email')" type="email" required disabled autocomplete="email" />
            </div>

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>

        <livewire:pages::settings.delete-user-form />
    </x-pages::settings.layout>

    <x-pages::settings.layout :heading="__('Update password')" :subheading="__('Ensure your account is using a long, random password to stay secure')">
        <form wire:submit="updatePassword" class="my-6 w-full space-y-6">
            @if (auth()->user()->password)
                <flux:input wire:model="current_password" :label="__('Current password')" type="password" required autocomplete="current-password" viewable />
            @endif

            <flux:input wire:model="password" :label="__('New password')" type="password" required autocomplete="new-password" viewable />

            <flux:input wire:model="password_confirmation" :label="__('Confirm password')" type="password" required autocomplete="new-password" viewable />

            <div class="flex items-center gap-4">
                <flux:button variant="primary" type="submit">{{ __('Save') }}</flux:button>
            </div>
        </form>
    </x-pages::settings.layout>
</section>
