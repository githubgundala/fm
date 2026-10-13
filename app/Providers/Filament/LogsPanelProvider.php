<?php

namespace App\Providers\Filament;

use AchyutN\FilamentLogViewer\FilamentLogViewer;
use App\Http\Middleware\EnsureLogViewerToken;
use Filament\Panel;
use Filament\PanelProvider;
use Filament\Support\Colors\Color;
use Illuminate\Cookie\Middleware\AddQueuedCookiesToResponse;
use Illuminate\Cookie\Middleware\EncryptCookies;
use Illuminate\Foundation\Http\Middleware\PreventRequestForgery;
use Illuminate\Routing\Middleware\SubstituteBindings;
use Illuminate\Session\Middleware\StartSession;
use Illuminate\View\Middleware\ShareErrorsFromSession;

class LogsPanelProvider extends PanelProvider
{
    public function panel(Panel $panel): Panel
    {
        return $panel
            ->id('logs')
            ->path('system-logs')
            ->brandName('Log Viewer')
            ->colors([
                'primary' => Color::Slate,
            ])
            ->middleware([
                EncryptCookies::class,
                AddQueuedCookiesToResponse::class,
                StartSession::class,
                ShareErrorsFromSession::class,
                PreventRequestForgery::class,
                SubstituteBindings::class,
                EnsureLogViewerToken::class,
            ])
            ->plugins([
                $this->logViewerPlugin(),
            ]);
    }

    protected function logViewerPlugin(): FilamentLogViewer
    {
        // FilamentLogViewer::make() returns a shared singleton, so clone it.
        // Otherwise per-panel customization like navigationUrl('/') would leak
        // into the admin panel and hijack its '/' dashboard route.
        return (clone FilamentLogViewer::make())
            ->authorize(true)
            ->navigationUrl('/');
    }
}
