<?php

namespace App\Providers\Filament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use App\Filament\Auth\Pages\EditProfile;
use App\Filament\Widgets\InvoiceStatsOverview;
use App\Filament\Widgets\OverdueInvoicesTable;
use App\Filament\Widgets\RevenueChart;
use App\Models\WebsiteSetting;
use BezhanSalleh\FilamentShield\FilamentShieldPlugin;
use Exception;
use Filament\Http\Middleware\Authenticate;
use Filament\Http\Middleware\AuthenticateSession;
use Filament\Http\Middleware\DisableBladeIconComponents;
use Filament\Http\Middleware\DispatchServingFilamentEvent;
use Filament\Pages\Dashboard;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Filament\Widgets\AccountWidget;
use Filament\Widgets\FilamentInfoWidget;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class AdminPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->default()
            ->id('admin')
            ->path('')
            ->login()
            ->profile(EditProfile::class)
            ->colors([
                'primary' => self::resolvePrimaryColor(),
            ])
            ->brandLogo(fn (): ?string => self::resolveLogoUrl())
            ->brandLogoHeight('2rem')
            ->favicon(fn (): ?string => self::resolveFaviconUrl() ?? asset('favicon.ico'))
            ->discoverResources(in: app_path('Filament/Resources'), for: 'App\Filament\Resources')
            ->discoverPages(in: app_path('Filament/Pages'), for: 'App\Filament\Pages')
            ->pages([
                Dashboard::class,
            ])
            ->discoverWidgets(in: app_path('Filament/Widgets'), for: 'App\Filament\Widgets')
            ->widgets([
                AccountWidget::class,
                FilamentInfoWidget::class,
                InvoiceStatsOverview::class,
                RevenueChart::class,
                OverdueInvoicesTable::class,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                AuthenticateSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                DisableBladeIconComponents::class,
                DispatchServingFilamentEvent::class,
            ])
            ->plugins([
                FilamentShieldPlugin::make()
                    ->navigationGroup('Pengaturan')
                    ->navigationSort(101),
                FilamentLogViewer::make(),
            ])
            ->authMiddleware([
                Authenticate::class,
            ]);
    }

    /**
     * @return array<int, string>
     */
    protected static function resolvePrimaryColor(): array
    {
        try {
            if (! Schema::hasTable('website_settings')) {
                return Color::Amber;
            }

            return WebsiteSetting::cached()->primaryColor();
        } catch (Exception) {
            return Color::Amber;
        }
    }

    protected static function resolveLogoUrl(): ?string
    {
        try {
            if (! Schema::hasTable('website_settings')) {
                return null;
            }

            return WebsiteSetting::cached()->logoUrl();
        } catch (Exception) {
            return null;
        }
    }

    protected static function resolveFaviconUrl(): ?string
    {
        try {
            if (! Schema::hasTable('website_settings')) {
                return null;
            }

            return WebsiteSetting::cached()->faviconUrl();
        } catch (Exception) {
            return null;
        }
    }
}
