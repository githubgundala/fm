<?php

namespace App\Filament\Resources\Quotations\RelationManagers;

use App\Services\QuotationConverter;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class InvoicesRelationManager extends RelationManager
{
    protected static string $relationship = 'invoices';

    protected static ?string $title = 'Invoice Terkait';

    public function table(Table $table): Table
    {
        return $table
            ->headerActions([
                Action::make('convert')
                    ->label('Buat Invoice Baru')
                    ->icon('heroicon-o-plus')
                    ->action(function (): void {
                        $invoice = app(QuotationConverter::class)->convert($this->getOwnerRecord());

                        Notification::make()
                            ->success()
                            ->title("Invoice {$invoice->invoice_number} dibuat.")
                            ->send();

                        $this->redirect($this->getPageClass()::getUrl(['record' => $this->getOwnerRecord()]));
                    }),
            ])
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor')
                    ->searchable(),
                TextColumn::make('status')
                    ->badge(),
                TextColumn::make('issue_date')
                    ->label('Terbit')
                    ->date(),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR'),
                TextColumn::make('amount_due')
                    ->label('Sisa')
                    ->money('IDR'),
            ]);
    }
}
