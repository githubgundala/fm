<?php

namespace App\Filament\Resources\Quotations\Pages;

use App\Enums\QuotationStatus;
use App\Filament\Resources\Quotations\QuotationResource;
use App\Models\Quotation;
use Filament\Actions\DeleteAction;
use Filament\Actions\ForceDeleteAction;
use Filament\Actions\RestoreAction;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;
use Filament\Support\Enums\Width;

class EditQuotation extends EditRecord
{
    protected static string $resource = QuotationResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    public function mount(int|string $record): void
    {
        parent::mount($record);

        /** @var Quotation $quotation */
        $quotation = $this->record;

        if (! $quotation->isEditable()) {
            Notification::make()
                ->warning()
                ->title("Quotation {$quotation->quotation_number} tidak dapat diubah karena berstatus {$quotation->status->value}.")
                ->send();

            $this->redirect(static::getResource()::getUrl('view', ['record' => $quotation]));
        }
    }

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make()
                ->visible(fn (Quotation $record): bool => $record->status === QuotationStatus::Draft),
            ForceDeleteAction::make(),
            RestoreAction::make(),
        ];
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
