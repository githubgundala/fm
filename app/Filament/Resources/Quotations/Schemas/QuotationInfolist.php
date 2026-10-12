<?php

namespace App\Filament\Resources\Quotations\Schemas;

use Filament\Infolists\Components\RepeatableEntry;
use Filament\Infolists\Components\RepeatableEntry\TableColumn;
use Filament\Infolists\Components\TextEntry;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;

class QuotationInfolist
{
    public static function configure(Schema $schema): Schema
    {
        return $schema
            ->columns(1)
            ->components([
                Section::make('Quotation')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('quotation_number')
                            ->label('Nomor'),
                        TextEntry::make('status')
                            ->badge(),
                        TextEntry::make('template.name')
                            ->label('Jenis Proyek')
                            ->placeholder('-'),
                        TextEntry::make('title')
                            ->label('Judul')
                            ->placeholder('-'),
                        TextEntry::make('issue_date')
                            ->label('Tanggal Terbit')
                            ->date(),
                        TextEntry::make('valid_until')
                            ->label('Berlaku Hingga')
                            ->date(),
                        TextEntry::make('client.name')
                            ->label('Klien'),
                        TextEntry::make('client.email')
                            ->label('Email Klien'),
                        TextEntry::make('receiver_name')
                            ->label('Nama Penerima')
                            ->placeholder('-'),
                    ])
                    ->columns(3),
                Section::make('Ringkasan Proyek')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('project_summary')
                            ->hiddenLabel()
                            ->placeholder('-'),
                    ])
                    ->collapsible(),
                Section::make('Item')
                    ->columnSpanFull()
                    ->schema([
                        RepeatableEntry::make('items')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Section'),
                                TableColumn::make('Deskripsi'),
                                TableColumn::make('Qty'),
                                TableColumn::make('Harga Satuan'),
                                TableColumn::make('Subtotal'),
                            ])
                            ->schema([
                                TextEntry::make('section'),
                                TextEntry::make('description'),
                                TextEntry::make('qty'),
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
                    ])
                    ->columns(3),
                Section::make('Invoice Terkait')
                    ->columnSpanFull()
                    ->description('Satu quotation dapat dikonversi menjadi banyak invoice.')
                    ->schema([
                        RepeatableEntry::make('invoices')
                            ->hiddenLabel()
                            ->table([
                                TableColumn::make('Nomor Invoice'),
                                TableColumn::make('Status'),
                                TableColumn::make('Grand Total'),
                            ])
                            ->schema([
                                TextEntry::make('invoice_number'),
                                TextEntry::make('status')
                                    ->badge(),
                                TextEntry::make('grand_total')
                                    ->money('IDR'),
                            ]),
                    ])
                    ->collapsible(),
                Section::make('Catatan')
                    ->columnSpanFull()
                    ->schema([
                        TextEntry::make('notes')
                            ->label('Catatan')
                            ->placeholder('-'),
                        TextEntry::make('terms')
                            ->label('Syarat & Ketentuan')
                            ->placeholder('-'),
                        TextEntry::make('exclusions')
                            ->label('Yang Tidak Termasuk')
                            ->placeholder('-'),
                    ])
                    ->columns(2)
                    ->collapsible(),
            ]);
    }
}
