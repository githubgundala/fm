<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Services\InvoiceNumberGenerator;
use App\Support\MoneyInput;
use App\Support\MoneyStateCast;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class PaymentForm
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Invoice & Pembayaran')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('payment_number')
                            ->label('Nomor Payment')
                            ->required()
                            ->maxLength(50)
                            ->unique(table: 'payments', column: 'payment_number', ignoreRecord: true)
                            ->default(fn (): string => app(InvoiceNumberGenerator::class)->peekFor(InvoiceNumberGenerator::TYPE_PAYMENT))
                            ->disabledOn('edit')
                            ->helperText('Terisi otomatis, dapat diubah sebelum disimpan.'),
                        Select::make('invoice_id')
                            ->label('Invoice')
                            ->options(
                                Invoice::query()
                                    ->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue])
                                    ->orderByDesc('issue_date')
                                    ->pluck('invoice_number', 'id')
                            )
                            ->searchable()
                            ->preload()
                            ->required(),
                        DatePicker::make('payment_date')
                            ->label('Tanggal Bayar')
                            ->default(now())
                            ->required(),
                        TextInput::make('amount')
                            ->label('Nominal')
                            ->required()
                            ->stateCast(new MoneyStateCast)
                            ->mask(MoneyInput::mask())
                            ->mutateStateForValidationUsing(fn (mixed $state): ?float => MoneyInput::toNumber($state))
                            ->rule('numeric')
                            ->minValue(0.01)
                            ->inputMode('numeric')
                            ->prefix('Rp')
                            ->helperText('Ketik angka saja, format ribuan otomatis. Contoh: 1.500.000'),
                        Select::make('method')
                            ->label('Metode')
                            ->options(PaymentMethod::class)
                            ->required(),
                    ])
                    ->columns(2),
                Section::make('Detail Tambahan')
                    ->columnSpanFull()
                    ->schema([
                        TextInput::make('reference')
                            ->label('Referensi')
                            ->helperText('No. transfer / VA / approval, jika ada.')
                            ->maxLength(100),
                        Textarea::make('notes')
                            ->label('Catatan')
                            ->helperText('Contoh: DP 30%.')
                            ->columnSpanFull(),
                        FileUpload::make('proof_path')
                            ->label('Bukti Pembayaran (opsional)')
                            ->disk('public')
                            ->directory('payment-proofs')
                            ->visibility('public')
                            ->image()
                            ->maxSize(2048)
                            ->helperText('Foto struk / screenshot transfer. Maks 2MB. Boleh dikosongkan.')
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }
}
