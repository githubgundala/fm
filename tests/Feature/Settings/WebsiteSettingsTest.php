<?php

use App\Filament\Pages\WebsiteSettingsPage;
use App\Models\User;
use App\Models\WebsiteSetting;
use Filament\Support\Colors\Color;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(superAdmin());
    Cache::forget(WebsiteSetting::CACHE_KEY);
    Storage::fake('public');
});

it('menyimpan warna tema preset dan membersihkan cache', function (): void {
    livewire(WebsiteSettingsPage::class)
        ->fillForm(['primary_color_key' => 'sky'])
        ->call('save')
        ->assertNotified()
        ->assertHasNoFormErrors();

    expect(WebsiteSetting::cached()->primary_color_key)->toBe('sky')
        ->and(WebsiteSetting::cached()->primaryColor())->toBe(Color::Sky);
});

it('memvalidasi warna tema harus preset yang didukung', function (): void {
    livewire(WebsiteSettingsPage::class)
        ->fillForm(['primary_color_key' => 'neon-tidak-ada'])
        ->call('save')
        ->assertHasFormErrors(['primary_color_key']);
});

it('terdaftar pada grup navigasi Pengaturan', function (): void {
    expect(WebsiteSettingsPage::getNavigationGroup())->toBe('Pengaturan')
        ->and(WebsiteSettingsPage::getNavigationLabel())->toBe('Pengaturan Website');
});

it('memetakan setiap preset ke palet Filament', function (): void {
    expect(WebsiteSetting::colorPalette('amber'))->toBe(Color::Amber)
        ->and(WebsiteSetting::colorPalette('blue'))->toBe(Color::Blue)
        ->and(WebsiteSetting::colorPalette('emerald'))->toBe(Color::Emerald)
        ->and(WebsiteSetting::colorPalette('sky'))->toBe(Color::Sky)
        ->and(WebsiteSetting::colorPalette('tidak-ada'))->toBe(Color::Amber);
});

it('mengembalikan null untuk logo dan favicon saat belum diunggah', function (): void {
    expect(WebsiteSetting::cached()->logoUrl())->toBeNull()
        ->and(WebsiteSetting::cached()->faviconUrl())->toBeNull();
});

it('menjalankan optimize:clear untuk super_admin', function (): void {
    livewire(WebsiteSettingsPage::class)
        ->call('runOptimizeClear')
        ->assertNotified();
});

it('menolak optimize:clear untuk user tanpa role super_admin', function (): void {
    $this->actingAs(User::factory()->create());

    livewire(WebsiteSettingsPage::class)
        ->call('runOptimizeClear')
        ->assertNotified('Anda tidak memiliki akses untuk menjalankan optimize:clear.');
});
