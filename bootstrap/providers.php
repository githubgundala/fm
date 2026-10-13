<?php

use App\Providers\AppServiceProvider;
use App\Providers\Filament\AdminPanelProvider;
use App\Providers\Filament\LogsPanelProvider;

return [
    AppServiceProvider::class,
    AdminPanelProvider::class,
    LogsPanelProvider::class,
];
