<?php

namespace App\Filament\Pages;

use App\Models\WebsiteSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;
use Throwable;
use UnitEnum;

/**
 * @property Schema $form
 */
class WebsiteSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCog6Tooth;

    protected static ?string $navigationLabel = 'Pengaturan Website';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 10;

    protected static ?string $title = 'Pengaturan Website';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(WebsiteSetting::cached()->toArray());
    }

    public function defaultForm(Schema $schema): Schema
    {
        return $schema
            ->statePath('data');
    }

    public function form(Schema $schema): Schema
    {
        return $schema
            ->components([
                Section::make('Warna Tema')
                    ->description('Warna primer panel Filament. Perubahan berlaku setelah halaman dimuat ulang.')
                    ->schema([
                        Select::make('primary_color_key')
                            ->label('Warna Primer')
                            ->options(WebsiteSetting::colorOptions())
                            ->required()
                            ->native(false),
                    ]),
                Section::make('Logo')
                    ->description('Logo yang tampil pada panel (sidebar dan halaman login).')
                    ->schema([
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('website')
                            ->visibility('public')
                            ->image()
                            ->maxSize(2048)
                            ->imagePreviewHeight('80')
                            ->columnSpanFull(),
                    ]),
                Section::make('Favicon')
                    ->description('Ikon tab browser. Disarankan PNG persegi atau ICO/SVG.')
                    ->schema([
                        FileUpload::make('favicon_path')
                            ->label('Favicon')
                            ->disk('public')
                            ->directory('website')
                            ->visibility('public')
                            ->image()
                            ->acceptedFileTypes(['image/png', 'image/jpeg', 'image/svg+xml', 'image/x-icon', 'image/vnd.microsoft.icon'])
                            ->maxSize(1024)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('optimizeClear')
                ->label('Optimize Clear')
                ->icon(Heroicon::OutlinedTrash)
                ->color('danger')
                ->requiresConfirmation()
                ->modalHeading('Jalankan optimize:clear?')
                ->modalDescription('Cache config, route, view, dan event akan dibersihkan.')
                ->visible(fn (): bool => (bool) auth()->user()?->hasRole('super_admin'))
                ->action(fn (): mixed => $this->runOptimizeClear()),
        ];
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('save')
                ->label('Simpan Pengaturan')
                ->submit('save'),
        ];
    }

    public function content(Schema $schema): Schema
    {
        return $schema
            ->components([
                $this->getFormContentComponent(),
            ]);
    }

    public function getFormContentComponent(): Component
    {
        return Form::make([EmbeddedSchema::make('form')])
            ->id('form')
            ->livewireSubmitHandler('save')
            ->footer([
                Actions::make($this->getFormActions())->key('form-actions'),
            ]);
    }

    public function save(): void
    {
        $data = $this->form->getState();
        $settings = WebsiteSetting::cached();
        $oldLogo = $settings->logo_path;
        $oldFavicon = $settings->favicon_path;

        $settings->update($data);

        if ($oldLogo && $oldLogo !== $settings->fresh()?->logo_path) {
            Storage::disk('public')->delete($oldLogo);
        }

        if ($oldFavicon && $oldFavicon !== $settings->fresh()?->favicon_path) {
            Storage::disk('public')->delete($oldFavicon);
        }

        Notification::make()
            ->success()
            ->title('Pengaturan website disimpan. Muat ulang halaman untuk melihat warna, logo, dan favicon baru.')
            ->send();
    }

    public function runOptimizeClear(): void
    {
        if (! auth()->user()?->hasRole('super_admin')) {
            Notification::make()
                ->danger()
                ->title('Anda tidak memiliki akses untuk menjalankan optimize:clear.')
                ->send();

            return;
        }

        try {
            Artisan::call('optimize:clear');
        } catch (Throwable $e) {
            report($e);

            Notification::make()
                ->danger()
                ->title('Optimize clear gagal.')
                ->body($e->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Optimize clear berhasil dijalankan.')
            ->send();
    }
}
