<?php

namespace App\Filament\Resources\Invoices\Pages;

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Models\Invoice;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditInvoice extends EditRecord
{
    protected static string $resource = InvoiceResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Invoice $invoice */
        $invoice = $this->record;

        if (! $invoice->isEditable()) {
            Notification::make()
                ->warning()
                ->title("Invoice {$invoice->invoice_number} tidak dapat diubah karena berstatus {$invoice->status->value}.")
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $invoice]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Invoice $record): bool => $record->status === InvoiceStatus::Draft),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
