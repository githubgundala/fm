<?php

namespace App\Filament\Resources\Payments\Tables;

use App\Enums\PaymentMethod;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PaymentsTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('payment_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('invoice.invoice_number')
                    ->label('Invoice')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('payment_date')
                    ->label('Tanggal')
                    ->date()
                    ->sortable(),
                TextColumn::make('amount')
                    ->label('Nominal')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('method')
                    ->label('Metode')
                    ->badge(),
                TextColumn::make('reference')
                    ->label('Referensi')
                    ->searchable()
                    ->toggleable(isToggledHiddenByDefault: true),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('method')
                    ->label('Metode')
                    ->options(PaymentMethod::class),
            ])
            ->recordActions([
                Action::make('printSlip')
                    ->label('Slip')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Payment $record): string => route('payments.slip', $record))
                    ->openUrlInNewTab(),
                ViewAction::make(),
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }
}
