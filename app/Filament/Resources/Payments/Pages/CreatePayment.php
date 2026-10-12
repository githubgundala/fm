<?php

namespace App\Filament\Resources\Payments\Pages;

use App\Enums\PaymentMethod;
use App\Filament\Resources\Payments\PaymentResource;
use App\Models\Invoice;
use App\Models\Payment;
use App\Services\PaymentRecorder;
use Filament\Resources\Pages\CreateRecord;
use Filament\Support\Enums\Width;
use Illuminate\Database\Eloquent\Model;

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

        /** @var Payment $payment */
        $payment = app(PaymentRecorder::class)->record(
            $invoice,
            is_numeric($amount) ? $amount : 0,
            $method instanceof PaymentMethod || is_string($method) ? $method : '',
            is_string($paymentDate) ? $paymentDate : null,
            is_string($reference) ? $reference : null,
            is_string($notes) ? $notes : null,
        );

        return $payment;
    }

    protected function getRedirectUrl(): string
    {
        return static::getResource()::getUrl('view', ['record' => $this->record]);
    }
}
