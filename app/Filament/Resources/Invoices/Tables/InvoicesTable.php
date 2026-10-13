<?php

namespace App\Filament\Resources\Invoices\Tables;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Actions\SendInvoiceEmailAction;
use App\Models\Invoice;
use App\Services\InvoiceDuplicator;
use Filament\Actions\Action;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteAction;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Actions\ForceDeleteBulkAction;
use Filament\Actions\RestoreBulkAction;
use Filament\Actions\ViewAction;
use Filament\Notifications\Notification;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Filters\TrashedFilter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class InvoicesTable
{
    public static function configure(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('invoice_number')
                    ->label('Nomor')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('client.name')
                    ->label('Klien')
                    ->searchable()
                    ->sortable(),
                TextColumn::make('issue_date')
                    ->label('Terbit')
                    ->date()
                    ->sortable(),
                TextColumn::make('due_date')
                    ->label('Jatuh Tempo')
                    ->date()
                    ->sortable(),
                TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'gray' => [InvoiceStatus::Draft, InvoiceStatus::Cancelled],
                        'info' => InvoiceStatus::Sent,
                        'warning' => InvoiceStatus::PartiallyPaid,
                        'success' => InvoiceStatus::Paid,
                        'danger' => InvoiceStatus::Overdue,
                    ]),
                TextColumn::make('grand_total')
                    ->label('Total')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('amount_due')
                    ->label('Sisa')
                    ->money('IDR')
                    ->sortable(),
                TextColumn::make('created_at')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                SelectFilter::make('status')
                    ->options(InvoiceStatus::class),
                Filter::make('overdue')
                    ->label('Jatuh Tempo / Terlambat')
                    ->query(fn (Builder $query): Builder => $query->where('due_date', '<', now()->format('Y-m-d'))->whereIn('status', [InvoiceStatus::Sent, InvoiceStatus::PartiallyPaid, InvoiceStatus::Overdue])),
                TrashedFilter::make(),
            ])
            ->recordActions([
                ViewAction::make(),
                EditAction::make()
                    ->visible(fn (Invoice $record): bool => in_array($record->status, [InvoiceStatus::Draft, InvoiceStatus::Sent], true)),
                Action::make('print')
                    ->label('Cetak')
                    ->icon('heroicon-o-printer')
                    ->url(fn (Invoice $record): string => route('invoices.print', $record))
                    ->openUrlInNewTab(),
                SendInvoiceEmailAction::make('sendEmail'),
                Action::make('duplicate')
                    ->label('Duplikat')
                    ->icon('heroicon-o-square-2-stack')
                    ->action(function (Invoice $record, InvoiceDuplicator $duplicator): void {
                        $copy = $duplicator->duplicate($record);

                        Notification::make()
                            ->success()
                            ->title("Invoice diduplikat menjadi {$copy->invoice_number}")
                            ->send();
                    }),
                DeleteAction::make()
                    ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                    ForceDeleteBulkAction::make(),
                    RestoreBulkAction::make(),
                ]),
            ]);
    }
}
