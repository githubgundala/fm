<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Payment;
use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;
use Filament\Support\Enums\Width;

class ViewPayment extends ViewRecord
{
    protected static string $resource = PaymentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function getHeaderActions(): array
    {
        return [
            Action::make('printSlip')
                ->label('Cetak Slip')
                ->icon('heroicon-o-printer')
                ->url(fn (Payment $record): string => route('payments.slip', $record))
                ->openUrlInNewTab(),
            EditAction::make(),
            DeleteAction::make(),
        ];
    }
}
