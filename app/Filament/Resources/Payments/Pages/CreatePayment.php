<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentRecorder;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Validation\ValidationException;

class CreatePayment extends CreateRecord
{
    protected static string $resource = PaymentResource::class;

    protected Width|string|null $maxContentWidth = Width::Full;

    protected function handleRecordCreation(array $data): Model
    {
        $invoice = Invoice::query()->whereKey($data['invoice_id'] ?? 0)->firstOrFail();

        $amount = $data['amount'] ?? 0;
        $method = $data['method'] ?? '';
        $paymentDate = $data['payment_date'] ?? null;
        $reference = $data['reference'] ?? null;
        $notes = $data['notes'] ?? null;
        $paymentNumber = $data['payment_number'] ?? null;

        /** @var Payment $payment */
        try {
            $payment = app(PaymentRecorder::class)->record(
                $invoice,
                is_numeric($amount) ? $amount : 0,
                $method instanceof PaymentMethod || is_string($method) ? $method : '',
                is_string($paymentDate) ? $paymentDate : null,
                is_string($reference) ? $reference : null,
                is_string($notes) ? $notes : null,
                is_string($paymentNumber) ? $paymentNumber : null,
            );
        } catch (ValidationException $exception) {
            Notification::make()
                ->danger()
                ->title('Pembayaran gagal disimpan.')
                ->body(collect($exception->errors())->flatten()->implode(' '))
                ->send();

            $this->halt();
        }

        return $payment;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
