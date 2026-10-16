<?php

namespace App\Filament\Resources\Payments;

use App\Filament\Resources\Payments\Pages\CreatePayment;
use App\Filament\Resources\Payments\Pages\EditPayment;
use App\Filament\Resources\Payments\Pages\ListPayments;
use App\Filament\Resources\Payments\Pages\ViewPayment;
use App\Filament\Resources\Payments\Schemas\PaymentForm;
use App\Filament\Resources\Payments\Tables\PaymentsTable;
use App\Models\Payment;
use BackedEnum;
use Filament\Infolists;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Table;
use UnitEnum;

class PaymentResource extends Resource
{
    protected static ?string $model = Payment::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBanknotes;

    protected static ?string $navigationLabel = 'Pembayaran';

    protected static string|UnitEnum|null $navigationGroup = 'Invoice & Quotation';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return PaymentForm::configure($schema);
    }

    public static function infolist(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Pembayaran')
                    ->columnSpanFull()
                    ->schema([
                        Infolists\Components\TextEntry::make('payment_number')
                            ->label('Nomor'),
                        Infolists\Components\TextEntry::make('payment_date')
                            ->label('Tanggal')
                            ->date(),
                        Infolists\Components\TextEntry::make('amount')
                            ->label('Nominal')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('method')
                            ->label('Metode')
                            ->badge(),
                        Infolists\Components\TextEntry::make('invoice.invoice_number')
                            ->label('Invoice'),
                        Infolists\Components\TextEntry::make('invoice.status')
                            ->label('Status Tagihan')
                            ->badge(),
                        Infolists\Components\TextEntry::make('invoice.amount_due')
                            ->label('Sisa Tagihan')
                            ->money('IDR'),
                        Infolists\Components\TextEntry::make('lunas')
                            ->label('Pelunasan')
                            ->badge()
                            ->state(fn (Payment $record): string => ($record->invoice?->isPaidOff() ?? false) ? 'LUNAS' : 'Belum Lunas')
                            ->color(fn (Payment $record): string => ($record->invoice?->isPaidOff() ?? false) ? 'success' : 'gray'),
                        Infolists\Components\TextEntry::make('reference')
                            ->label('Referensi')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Catatan')
                    ->columnSpanFull()
                    ->schema([
                        Infolists\Components\TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return PaymentsTable::configure($table);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListPayments::route('/'),
            'create' => CreatePayment::route('/create'),
            'view' => ViewPayment::route('/{record}'),
            'edit' => EditPayment::route('/{record}/edit'),
        ];
    }
}
