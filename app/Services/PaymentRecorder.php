<?php

namespace App\Services;

use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Payment;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PaymentRecorder
{
    /**
     * Record a payment (including DP / installments) against an invoice.
     *
     * @throws ValidationException
     */
    public function record(
        Invoice $invoice,
        float|int|string $amount,
        PaymentMethod|string $method,
        ?string $paymentDate = null,
        ?string $reference = null,
        ?string $notes = null,
        ?string $paymentNumber = null,
    ): Payment {
        if (! $invoice->canReceivePayment()) {
            throw ValidationException::withMessages([
                'invoice_id' => "Invoice {$invoice->invoice_number} berstatus {$invoice->status->value} sehingga tidak dapat menerima pembayaran.",
            ]);
        }

        $amountValue = round((float) $amount, 2);

        if ($amountValue <= 0) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal pembayaran harus lebih dari 0.',
            ]);
        }

        $amountDue = round((float) $invoice->refresh()->amount_due, 2);

        if (! InvoiceSetting::cached()->allow_overpayment && $amountValue > $amountDue) {
            throw ValidationException::withMessages([
                'amount' => 'Nominal melebihi sisa tagihan Rp '.number_format($amountDue, 2, ',', '.').'.',
            ]);
        }

        $methodValue = $method instanceof PaymentMethod ? $method : PaymentMethod::tryFrom((string) $method);

        if (! $methodValue instanceof PaymentMethod) {
            throw ValidationException::withMessages([
                'method' => 'Metode pembayaran tidak valid.',
            ]);
        }

        return DB::transaction(
            fn (): Payment => $invoice->payments()->create([
                'payment_number' => $this->resolvePaymentNumber($paymentNumber),
                'payment_date' => $paymentDate ?? now()->format('Y-m-d'),
                'amount' => $amountValue,
                'method' => $methodValue,
                'reference' => $reference,
                'notes' => $notes,
                'received_by' => auth()->id(),
            ])
        );
    }

    private function resolvePaymentNumber(?string $paymentNumber): string
    {
        $generator = app(InvoiceNumberGenerator::class);
        $given = trim((string) ($paymentNumber ?? ''));

        if ($given === '' || $given === $generator->peekFor(InvoiceNumberGenerator::TYPE_PAYMENT)) {
            return $generator->next(InvoiceNumberGenerator::TYPE_PAYMENT);
        }

        if (Payment::query()->where('payment_number', $given)->exists()) {
            return $generator->next(InvoiceNumberGenerator::TYPE_PAYMENT);
        }

        $generator->syncCounter($given, InvoiceNumberGenerator::TYPE_PAYMENT);

        return $given;
    }
}
