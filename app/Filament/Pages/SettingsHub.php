<?php

namespace App\Filament\Pages;

use BackedEnum;
use Filament\Actions\Action;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use UnitEnum;

class SettingsHub extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog;

    protected static ?string $navigationLabel = 'Pengaturan';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Pengaturan';

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Umum')
                    ->description('Pengaturan global aplikasi.')
                    ->schema([
                        Actions::make([
                            Action::make('invoiceSettings')
                                ->label('Pengaturan Invoice')
                                ->icon('heroicon-o-document-text')
                                ->url(InvoiceSettingsPage::getUrl()),
                        ]),
                    ]),
            ]);
    }
}
