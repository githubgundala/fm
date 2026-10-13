<?php

use App\Filament\Pages\OutgoingMailSettingsPage;
use App\Models\OutgoingMailSetting;
use App\Services\OutgoingMailConfigurator;
use App\Services\OutgoingMailTester;
use Illuminate\Support\Facades\Cache;
use RuntimeException;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(superAdmin());
    Cache::forget(OutgoingMailSetting::CACHE_KEY);
});

it('menyimpan pengaturan email keluar dan membersihkan cache', function (): void {
    livewire(OutgoingMailSettingsPage::class)
        ->fillForm([
            'host' => 'smtp.hostinger.com',
            'port' => 465,
            'encryption' => 'ssl',
            'username' => 'info@example.com',
            'password' => 'rahasia123',
            'from_address' => 'info@example.com',
            'from_name' => 'PT Contoh',
            'reply_to' => null,
            'is_active' => true,
        ])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect(OutgoingMailSetting::cached()->host)->toBe('smtp.hostinger.com')
        ->and(OutgoingMailSetting::cached()->port)->toBe(465)
        ->and(OutgoingMailSetting::cached()->is_active)->toBeTrue();
});

it('mempertahankan password lama saat password dikosongkan', function (): void {
    OutgoingMailSetting::cached()->update([
        'username' => 'info@example.com',
        'from_address' => 'info@example.com',
        'password' => 'lama123',
    ]);

    livewire(OutgoingMailSettingsPage::class)
        ->fillForm(['password' => null])
        ->call('save')
        ->assertHasNoFormErrors();

    expect(OutgoingMailSetting::cached()->password)->toBe('lama123');
});

it('memvalidasi field wajib pengaturan email keluar', function (): void {
    livewire(OutgoingMailSettingsPage::class)
        ->fillForm([
            'host' => null,
            'port' => 0,
            'from_address' => 'bukan-email',
        ])
        ->call('save')
        ->assertHasFormErrors(['host', 'port', 'from_address']);
});

it('terdaftar pada grup navigasi Pengaturan', function (): void {
    expect(OutgoingMailSettingsPage::getNavigationGroup())->toBe('Pengaturan')
        ->and(OutgoingMailSettingsPage::getNavigationLabel())->toBe('Pengaturan Email');
});

it('menerapkan konfigurasi SMTP saat pengaturan aktif', function (): void {
    OutgoingMailSetting::cached()->update([
        'host' => 'smtp.hostinger.com',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'info@example.com',
        'password' => 'rahasia123',
        'from_address' => 'info@example.com',
        'from_name' => 'PT Contoh',
        'is_active' => true,
    ]);

    app(OutgoingMailConfigurator::class)->apply();

    expect(config('mail.default'))->toBe('smtp')
        ->and(config('mail.mailers.smtp.host'))->toBe('smtp.hostinger.com')
        ->and(config('mail.mailers.smtp.scheme'))->toBe('smtps')
        ->and(config('mail.from.address'))->toBe('info@example.com');
});

it('melewati konfigurasi saat pengaturan nonaktif', function (): void {
    $defaultMailer = config('mail.default');

    OutgoingMailSetting::cached()->update(['is_active' => false]);

    app(OutgoingMailConfigurator::class)->apply();

    expect(config('mail.default'))->toBe($defaultMailer);
});

it('menolak tes kirim dengan konfigurasi tidak valid tanpa menyentuh jaringan', function (): void {
    app(OutgoingMailTester::class)->sendTest([
        'host' => '',
        'port' => 465,
        'encryption' => 'ssl',
        'username' => 'info@example.com',
        'password' => 'rahasia123',
        'from_address' => 'info@example.com',
        'from_name' => null,
        'reply_to' => null,
    ], 'tujuan@example.com');
})->throws(RuntimeException::class, 'Host dan port SMTP tidak valid.');
