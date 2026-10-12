<?php

namespace App\Filament\Pages;

use App\Models\InvoiceSetting;
use BackedEnum;
use Filament\Actions\Action;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Textarea;
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
use UnitEnum;

/**
 * @property Schema $form
 */
class InvoiceSettingsPage extends Page
{
    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedDocumentText;

    protected static ?string $navigationLabel = 'Pengaturan Invoice';

    protected static string|UnitEnum|null $navigationGroup = 'Pengaturan';

    protected static ?int $navigationSort = 11;

    protected static ?string $title = 'Pengaturan Invoice';

    /** @var array<string, mixed> */
    public array $data = [];

    public function mount(): void
    {
        $this->form->fill(InvoiceSetting::cached()->toArray());
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
                Section::make('Profil Perusahaan')
                    ->schema([
                        TextInput::make('company_name')
                            ->label('Nama Perusahaan')
                            ->required()
                            ->maxLength(255),
                        Textarea::make('company_address')
                            ->label('Alamat')
                            ->columnSpanFull(),
                        TextInput::make('company_phone')
                            ->label('Telepon')
                            ->tel()
                            ->maxLength(50),
                        TextInput::make('company_email')
                            ->label('Email')
                            ->email()
                            ->maxLength(255),
                        FileUpload::make('logo_path')
                            ->label('Logo')
                            ->disk('public')
                            ->directory('invoice-logos')
                            ->visibility('public')
                            ->image()
                            ->maxSize(10240)
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
                Section::make('Tanda Tangan')
                    ->description('Gambar dan nama penanda tangan yang tampil pada invoice (PDF & cetak).')
                    ->schema([
                        FileUpload::make('signature_path')
                            ->label('Gambar Tanda Tangan')
                            ->disk('public')
                            ->directory('invoice-signatures')
                            ->visibility('public')
                            ->image()
                            ->maxSize(10240)
                            ->columnSpanFull(),
                        TextInput::make('signature_name')
                            ->label('Nama Penanda Tangan')
                            ->placeholder('cth. Budi Santoso')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        TextInput::make('slip_receiver_name')
                            ->label('Nama Penerima (Slip Pembayaran)')
                            ->placeholder('cth. Sari Kasir')
                            ->helperText('Tampil pada kolom "Diterima oleh" di slip pembayaran.')
                            ->maxLength(255)
                            ->columnSpanFull(),
                    ]),
                Section::make('Penomoran')
                    ->description('Nomor dokumen dibuat otomatis dan direset setiap tahun.')
                    ->schema([
                        TextInput::make('invoice_prefix')
                            ->label('Prefix Invoice')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('payment_prefix')
                            ->label('Prefix Pembayaran')
                            ->required()
                            ->maxLength(10),
                        TextInput::make('quotation_prefix')
                            ->label('Prefix Quotation')
                            ->required()
                            ->maxLength(10)
                            ->helperText('Format nomor: PREFIX/YYYY/MM/NNN, reset tiap bulan.'),
                        Toggle::make('yearly_reset')
                            ->label('Reset tiap tahun')
                            ->disabled()
                            ->helperText('Dikunci: penomoran selalu direset setiap 1 Januari.'),
                    ])
                    ->columns(3),
                Section::make('Nilai Default')
                    ->schema([
                        TextInput::make('default_due_days')
                            ->label('Jatuh tempo default (hari)')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('default_quotation_validity_days')
                            ->label('Berlaku quotation default (hari)')
                            ->required()
                            ->numeric()
                            ->minValue(0),
                        TextInput::make('default_tax_rate')
                            ->label('Pajak default (%) — opsional')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->maxValue(100),
                        Textarea::make('default_terms')
                            ->label('Syarat default')
                            ->columnSpanFull(),
                        Textarea::make('default_notes')
                            ->label('Catatan default')
                            ->columnSpanFull(),
                        Toggle::make('allow_overpayment')
                            ->label('Izinkan kelebihan bayar')
                            ->helperText('Jika aktif, pembayaran boleh melebihi sisa tagihan.'),
                    ])
                    ->columns(2),
                Section::make('Template Email')
                    ->description('Placeholder: {invoice_number}, {client_name}, {company_name}, {amount_due}, {due_date}.')
                    ->schema([
                        TextInput::make('email_subject_template')
                            ->label('Subjek')
                            ->required()
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('email_body_template')
                            ->label('Isi')
                            ->rows(6)
                            ->columnSpanFull(),
                    ]),
            ]);
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

        InvoiceSetting::cached()->update($data);

        Notification::make()
            ->success()
            ->title('Pengaturan invoice disimpan.')
            ->send();
    }
}
