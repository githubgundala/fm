<?php

namespace App\Filament\Resources\Payments\Schemas;

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use Filament\Forms\Components\DatePicker;
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
                            ->numeric()
                            ->minValue(0.01)
                            ->prefix('Rp'),
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
                    ])
                    ->columns(2),
            ]);
    }
}
