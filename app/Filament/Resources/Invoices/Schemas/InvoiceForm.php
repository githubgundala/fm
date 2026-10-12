<?php

namespace App\Filament\Resources\Invoices\Schemas;

use App\Enums\DiscountType;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Services\InvoiceNumberGenerator;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class InvoiceForm
{
    public static function configure(Schema $schema): Schema
    {
        $record = $schema->getRecord();
        $isLocked = $record instanceof Invoice && ! $record->isEditable();

        return $schema
            ->columns(1)
            ->components([
                Section::make('Klien & Tanggal')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('invoice_number')
                            ->label('Nomor Invoice')
                            ->required()
                            ->maxLength(50)
                            ->unique(table: 'invoices', column: 'invoice_number', ignoreRecord: true)
                            ->default(fn (): string => app(InvoiceNumberGenerator::class)->peek())
                            ->disabledOn('edit')
                            ->helperText('Terisi otomatis, dapat diubah sebelum disimpan.'),
                        Select::make('client_id')
                            ->label('Klien')
                            ->relationship('client', 'name')
                            ->searchable()
                            ->preload()
                            ->required()
                            ->createOptionForm([
                                TextInput::make('name')
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('company')
                                    ->maxLength(255),
                                TextInput::make('email')
                                    ->email()
                                    ->required()
                                    ->maxLength(255),
                                TextInput::make('phone')
                                    ->tel()
                                    ->maxLength(50),
                            ]),
                        DatePicker::make('issue_date')
                            ->label('Tanggal Terbit')
                            ->default(now())
                            ->required(),
                        DatePicker::make('due_date')
                            ->label('Jatuh Tempo')
                            ->required()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $issueDate = $get('issue_date');

                                    if ($issueDate && $value && Carbon::parse($value)->lt(Carbon::parse($issueDate))) {
                                        $fail('Jatuh tempo tidak boleh sebelum tanggal terbit.');
                                    }
                                },
                            ]),
                    ])
                    ->columns(3),
                Section::make('Item Invoice')
                    ->columnSpanFull()
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Textarea::make('description')
                                    ->label('Deskripsi')
                                    ->required()
                                    ->maxLength(500)
                                    ->rows(3)
                                    ->columnSpan(2),
                                TextInput::make('qty')
                                    ->label('Qty')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0.01)
                                    ->default(1)
                                    ->live(onBlur: true),
                                TextInput::make('unit_price')
                                    ->label('Harga Satuan')
                                    ->required()
                                    ->numeric()
                                    ->minValue(0)
                                    ->prefix('Rp')
                                    ->live(onBlur: true),
                            ])
                            ->columns(4)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->disabled($isLocked)
                            ->helperText($isLocked ? 'Item dikunci karena invoice sudah tidak berstatus draft.' : null),
                    ]),
                Section::make('Diskon, Pajak & Catatan')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('discount_type')
                            ->label('Jenis Diskon')
                            ->options(DiscountType::class)
                            ->nullable()
                            ->disabled($isLocked),
                        TextInput::make('discount_value')
                            ->label('Nilai Diskon')
                            ->numeric()
                            ->default(0)
                            ->minValue(0)
                            ->disabled($isLocked),
                        TextInput::make('tax_rate')
                            ->label('Pajak (%) — opsional')
                            ->helperText('Kosongkan jika tidak ada pajak.')
                            ->numeric()
                            ->nullable()
                            ->minValue(0)
                            ->maxValue(100)
                            ->disabled($isLocked),
                        TextInput::make('currency')
                            ->label('Mata Uang')
                            ->required()
                            ->default('IDR')
                            ->maxLength(3)
                            ->disabled($isLocked),
                        TextInput::make('receiver_name')
                            ->label('Nama Penerima')
                            ->placeholder('cth. Andi (PT Maju)')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                        Textarea::make('terms')
                            ->label('Syarat & Ketentuan')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Default form values for a new invoice, taken from settings.
     *
     * @return array<string, mixed>
     */
    public static function creationDefaults(): array
    {
        $settings = InvoiceSetting::cached();

        return [
            'issue_date' => now()->format('Y-m-d'),
            'due_date' => now()->addDays($settings->default_due_days)->format('Y-m-d'),
            'tax_rate' => $settings->default_tax_rate,
            'notes' => $settings->default_notes,
            'terms' => $settings->default_terms,
            'currency' => 'IDR',
        ];
    }
}
