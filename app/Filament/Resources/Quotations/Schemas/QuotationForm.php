<?php

namespace App\Filament\Resources\Quotations\Schemas;

use App\Enums\DiscountType;
use App\Models\InvoiceSetting;
use App\Models\Quotation;
use App\Models\QuotationTemplate;
use Closure;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Components\Utilities\Get;
use Filament\Schemas\Components\Utilities\Set;
use Filament\Schemas\Schema;
use Illuminate\Support\Carbon;

class QuotationForm
{
    public static function configure(Schema $schema): Schema
    {
        $record = $schema->getRecord();
        $isLocked = $record instanceof Quotation && ! $record->isEditable();

        return $schema
            ->columns(1)
            ->components([
                Section::make('Template, Klien & Tanggal')
                    ->columnSpanFull()
                    ->schema([
                        Select::make('template_id')
                            ->label('Jenis Proyek / Template')
                            ->relationship('template', 'name')
                            ->searchable()
                            ->preload()
                            ->nullable()
                            ->live()
                            ->afterStateUpdated(function (Set $set, Get $get, ?string $state): void {
                                if (! $state) {
                                    return;
                                }

                                $template = QuotationTemplate::find($state);

                                if (! $template) {
                                    return;
                                }

                                if (! $get('title')) {
                                    $set('title', $template->default_title);
                                }

                                if (! $get('project_summary')) {
                                    $set('project_summary', $template->default_summary);
                                }

                                if (! $get('terms')) {
                                    $set('terms', $template->default_terms);
                                }

                                if (! $get('exclusions')) {
                                    $set('exclusions', $template->default_exclusions);
                                }

                                if (! $get('notes')) {
                                    $set('notes', $template->default_notes);
                                }

                                if ($template->default_tax_rate !== null) {
                                    $set('tax_rate', $template->default_tax_rate);
                                }

                                $issueDate = $get('issue_date') ? Carbon::parse($get('issue_date')) : now();
                                $set('valid_until', $issueDate->copy()->addDays($template->default_validity_days)->format('Y-m-d'));
                            }),
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
                            ->required()
                            ->live(),
                        DatePicker::make('valid_until')
                            ->label('Berlaku Hingga')
                            ->required()
                            ->rules([
                                fn (Get $get): Closure => function (string $attribute, mixed $value, Closure $fail) use ($get): void {
                                    $issueDate = $get('issue_date');

                                    if ($issueDate && $value && Carbon::parse($value)->lt(Carbon::parse($issueDate))) {
                                        $fail('Berlaku hingga tidak boleh sebelum tanggal terbit.');
                                    }
                                },
                            ]),
                    ])
                    ->columns(2),
                Section::make('Judul & Ringkasan Proyek')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('title')
                            ->label('Judul Penawaran')
                            ->placeholder('cth. Sistem ERP — Procure-to-Pay & Order-to-Cash')
                            ->maxLength(255)
                            ->columnSpanFull(),
                        Textarea::make('project_summary')
                            ->label('Ringkasan Proyek')
                            ->rows(4)
                            ->columnSpanFull(),
                    ]),
                Section::make('Item Penawaran')
                    ->columnSpanFull()
                    ->description('Kelompokkan item per section bebas (cth. Scope, Tech Stack, Timeline, Investasi). Section yang sama akan tampil dalam satu tabel di PDF.')
                    ->schema([
                        Repeater::make('items')
                            ->relationship()
                            ->schema([
                                TextInput::make('section')
                                    ->label('Section')
                                    ->placeholder('cth. Scope / Investasi / Timeline')
                                    ->maxLength(100)
                                    ->default('Item')
                                    ->datalist(['Ringkasan', 'Scope', 'Tech Stack', 'Timeline', 'Investasi', 'Syarat']),
                                TextInput::make('description')
                                    ->label('Deskripsi')
                                    ->required()
                                    ->maxLength(500)
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
                            ->columns(5)
                            ->minItems(1)
                            ->defaultItems(1)
                            ->disabled($isLocked)
                            ->helperText($isLocked ? 'Item dikunci karena quotation sudah tidak berstatus draft.' : null),
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
                            ->helperText('Default 11%. Kosongkan jika tidak ada pajak.')
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
                        TextInput::make('sender_name')
                            ->label('Nama Pengirim (penanda tangan)')
                            ->placeholder('cth. Andicha Gundala R. Putra')
                            ->maxLength(255),
                        TextInput::make('receiver_name')
                            ->label('Nama Penerima')
                            ->placeholder('cth. Muhammad Mirza')
                            ->maxLength(255),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->columnSpanFull(),
                        Textarea::make('terms')
                            ->label('Syarat & Ketentuan')
                            ->columnSpanFull(),
                        Textarea::make('exclusions')
                            ->label('Yang Tidak Termasuk')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    /**
     * Default form values for a new quotation, taken from settings.
     *
     * @return array<string, mixed>
     */
    public static function creationDefaults(): array
    {
        $settings = InvoiceSetting::cached();

        return [
            'issue_date' => now()->format('Y-m-d'),
            'valid_until' => now()->addDays($settings->default_quotation_validity_days ?? 3)->format('Y-m-d'),
            'tax_rate' => $settings->default_tax_rate,
            'notes' => $settings->default_notes,
            'terms' => $settings->default_terms,
            'currency' => 'IDR',
        ];
    }
}
