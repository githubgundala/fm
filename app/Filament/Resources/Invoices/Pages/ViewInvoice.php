<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Actions\SendInvoiceEmailAction;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use App\Services\InvoiceDuplicator;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewInvoice extends ViewRecord
{
    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('print')
                ->label('Cetak')
                ->icon('heroicon-o-printer')
                ->url(fn (Invoice $record): string => route('invoices.print', $record))
                ->openUrlInNewTab(),
            SendInvoiceEmailAction::make('sendEmail'),            Action::make('markSent')
                ->label('Tandai Terkirim')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft)
                ->action(function (Invoice $record): void {
                    $record->forceFill([
                        'status' => InvoiceStatus::Sent,
                        'sent_at' => $record->sent_at ?? now(),
                    ])->save();

                    Notification::make()
                        ->success()
                        ->title("Invoice {$record->invoice_number} ditandai terkirim.")
                        ->send();
                }),
            Action::make('markCancelled')
                ->label('Batalkan')
                ->icon('heroicon-o-x-circle')
                ->color('danger')
                ->requiresConfirmation()
                ->visible(fn (Invoice $record): bool => ! in_array($record->status, [InvoiceStatus::Paid, InvoiceStatus::Cancelled], true))
                ->action(function (Invoice $record): void {
                    $record->forceFill(['status' => InvoiceStatus::Cancelled])->save();

                    Notification::make()
                        ->success()
                        ->title("Invoice {$record->invoice_number} dibatalkan.")
                        ->send();
                }),
            Action::make('duplicate')
                ->label('Duplikat')
                ->icon('heroicon-o-square-2-stack')
                ->action(function (Invoice $record, InvoiceDuplicator $duplicator): void {
                    $copy = $duplicator->duplicate($record);

                    Notification::make()
                        ->success()
                        ->title("Invoice diduplikat menjadi {$copy->invoice_number}.")
                        ->send();

                    $this->redirect(static::getResource()::getUrl('edit', ['record' => $copy]));
                }),
            EditAction::make()
                ->visible(fn (Invoice $record): bool => $record->isEditable()),
            DeleteAction::make()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft),
        ];
    }
}
