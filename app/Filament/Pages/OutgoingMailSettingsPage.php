<?php

namespace App\Filament\Pages;

use App\Models\OutgoingMailSetting;
use App\Services\OutgoingMailConfigurator;
use App\Services\OutgoingMailTester;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Schemas\Components\Actions;
use Filament\Schemas\Components\Component;
use Filament\Schemas\Components\EmbeddedSchema;
use Filament\Schemas\Components\Form;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use RuntimeException;
use UnitEnum;

/**
 * @property Schema $form
 */
class OutgoingMailSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedEnvelope;

    protected static ?string $navigationLabel = 'Pengaturan Email';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 12;

    protected static ?string $title = 'Pengaturan Email';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $data = OutgoingMailSetting::cached()->toArray();
        $data['password'] = '';

        $this->form->fill($data);
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
                Section::make('Server SMTP')
                    ->description('Konfigurasi server email keluar. Untuk Hostinger gunakan host smtp.hostinger.com dengan port 465 (SSL) atau 587 (TLS).')
                    ->schema([
                        TextInput::make('host')
                            ->label('Host SMTP')
                            ->placeholder('smtp.hostinger.com')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('port')
                            ->label('Port')
                            ->required()
                            ->numeric()
                            ->minValue(1)
                            ->maxValue(65535),
                        Select::make('encryption')
                            ->label('Enkripsi')
                            ->options([
                                'ssl' => 'SSL (port 465)',
                                'tls' => 'TLS / STARTTLS (port 587)',
                                'none' => 'Tanpa enkripsi',
                            ])
                            ->required(),
                    ])
                    ->columns(3),
                Section::make('Kredensial')
                    ->schema([
                        TextInput::make('username')
                            ->label('Username')
                            ->placeholder('cth. info@perusahaan.co.id')
                            ->required()
                            ->maxLength(255),
                        TextInput::make('password')
                            ->label('Password')
                            ->password()
                            ->revealable()
                            ->helperText('Kosongkan untuk mempertahankan password yang tersimpan.'),
                    ])
                    ->columns(2),
                Section::make('Pengirim')
                    ->schema([
                        TextInput::make('from_address')
                            ->label('Alamat pengirim (From)')
                            ->placeholder('cth. info@perusahaan.co.id')
                            ->required()
                            ->email()
                            ->maxLength(255),
                        TextInput::make('from_name')
                            ->label('Nama pengirim')
                            ->placeholder('cth. PT Contoh Sejahtera')
                            ->maxLength(255),
                        TextInput::make('reply_to')
                            ->label('Balas ke (Reply-To)')
                            ->email()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Toggle::make('is_active')
                            ->label('Gunakan pengaturan ini untuk mengirim email')
                            ->helperText('Jika aktif, pengiriman invoice memakai SMTP di atas. Jika nonaktif, memakai konfigurasi .env.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * @return array<Action>
     */
    protected function getFormActions(): array
    {
        return [
            Action::make('testSend')
                ->label('Tes Kirim')
                ->color('gray')
                ->schema([
                    TextInput::make('test_address')
                        ->label('Kirim email tes ke')
                        ->placeholder('cth. anda@email.com')
                        ->required()
                        ->email()
                        ->maxLength(255),
                ])
                ->action(function (array $data): void {
                    $this->sendTest($data['test_address']);
                }),
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

        if (! filled($data['password'] ?? null)) {
            unset($data['password']);
        }

        OutgoingMailSetting::cached()->update($data);

        app(OutgoingMailConfigurator::class)->apply();

        Notification::make()
            ->success()
            ->title('Pengaturan email disimpan.')
            ->send();
    }

    public function sendTest(string $to): void
    {
        $data = $this->form->getState();

        if (! filled($data['password'] ?? null)) {
            $data['password'] = OutgoingMailSetting::cached()->password;
        }

        try {
            app(OutgoingMailTester::class)->sendTest($data, $to);
        } catch (RuntimeException $e) {
            Notification::make()
                ->danger()
                ->title('Tes kirim gagal.')
                ->body($e->getMessage())
                ->send();

            return;
        }

        Notification::make()
            ->success()
            ->title('Email tes terkirim ke '.$to.'.')
            ->send();
    }
}
