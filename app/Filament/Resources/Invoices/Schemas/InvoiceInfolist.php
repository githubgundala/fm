<?php

namespace App\Filament\Resources\Invoices\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class InvoiceInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Invoice')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('invoice_number')
                            ->label('Nomor'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('issue_date')
                            ->label('Tanggal Terbit')
                            ->date(),
                        TextEntry::make('due_date')
                            ->label('Jatuh Tempo')
                            ->date(),
                        TextEntry::make('client.name')
                            ->label('Klien'),
                        TextEntry::make('client.email')
                            ->label('Email Klien')
                            ->placeholder('-'),
                        TextEntry::make('receiver_name')
                            ->label('Nama Penerima')
                            ->placeholder('-'),
                        TextEntry::make('billing_address')
                            ->label('Alamat')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Item')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Deskripsi'),
                                TableColumn::make('Qty'),
                                TableColumn::make('Harga Satuan'),
                                TableColumn::make('Subtotal'),
                            ])
                            ->schema([
                                TextEntry::make('description'),
                                TextEntry::make('qty')
                                    ->formatStateUsing(fn ($state): string => rtrim(rtrim(number_format((float) $state, 2), '0'), '.')),
                                TextEntry::make('unit_price')
                                    ->money('IDR'),
                                TextEntry::make('line_total')
                                    ->money('IDR'),
                            ]),
                    ]),
                Section::make('Total')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('subtotal')
                            ->money('IDR'),
                        TextEntry::make('tax_amount')
                            ->label('Pajak')
                            ->money('IDR'),
                        TextEntry::make('grand_total')
                            ->label('Grand Total')
                            ->money('IDR')
                            ->weight('bold'),
                        TextEntry::make('amount_paid')
                            ->label('Terbayar')
                            ->money('IDR'),
                        TextEntry::make('amount_due')
                            ->label('Sisa Tagihan')
                            ->money('IDR')
                            ->weight('bold'),
                    ])
                    ->columns(3),
                Section::make('Catatan')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                        TextEntry::make('terms')
                            ->label('Syarat & Ketentuan')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
