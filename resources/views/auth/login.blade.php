<x-layouts::auth>
    <form method="POST" action="{{ route('login.attempt') }}" class="space-y-6">
        @csrf

        <div>
            <flux:heading size="lg">Masuk ke akun Anda</flux:heading>
            <flux:subheading>Selamat datang kembali.</flux:subheading>
        </div>

        <flux:input name="email" :label="__('Email')" type="email" required autofocus autocomplete="email" :value="old('email')" />

        <flux:input name="password" :label="__('Password')" type="password" required autocomplete="current-password" viewable />

        <flux:checkbox name="remember" :label="__('Remember me')" />

        <flux:button variant="primary" type="submit" class="w-full">{{ __('Log in') }}</flux:button>
    </form>
</x-layouts::auth>
