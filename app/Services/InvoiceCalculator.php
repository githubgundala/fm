<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Enums\InvoiceStatus;
use App\Models\Invoice;

class InvoiceCalculator
{
    /**
     * Calculate discount, tax, and grand total from a subtotal.
     *
     * @return array{subtotal: float, discountAmount: float, taxAmount: float, grandTotal: float}
     */
    public function calculate(float $subtotal, ?DiscountType $discountType, float $discountValue, ?float $taxRate): array
    {
        $discountAmount = match (true) {
            $discountType === DiscountType::Fixed => min($discountValue, $subtotal),
            $discountType === DiscountType::Percent => $subtotal * $discountValue / 100,
            default => 0.0,
        };

        $taxable = max($subtotal - $discountAmount, 0.0);
        $taxAmount = $taxRate === null ? 0.0 : $taxable * $taxRate / 100;

        return [
            'subtotal' => round($subtotal, 2),
            'discountAmount' => round($discountAmount, 2),
            'taxAmount' => round($taxAmount, 2),
            'grandTotal' => round($taxable + $taxAmount, 2),
        ];
    }

    /**
     * Recalculate cached totals and payment-driven status for an invoice.
     */
    public function recalculate(Invoice $invoice): void
    {
        $subtotal = (float) $invoice->items()->sum('line_total');
        $amountPaid = (float) $invoice->payments()->sum('amount');

        $totals = $this->calculate(
            $subtotal,
            $invoice->discount_type,
            (float) $invoice->discount_value,
            $invoice->tax_rate === null ? null : (float) $invoice->tax_rate,
        );

        $invoice->forceFill([
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['taxAmount'],
            'grand_total' => $totals['grandTotal'],
            'amount_paid' => round($amountPaid, 2),
            'amount_due' => round($totals['grandTotal'] - $amountPaid, 2),
        ])->saveQuietly();

        $this->refreshPaymentStatus($invoice, $amountPaid, $totals['grandTotal']);
    }

    private function refreshPaymentStatus(Invoice $invoice, float $amountPaid, float $grandTotal): void
    {
        if ($invoice->status === InvoiceStatus::Cancelled || $invoice->status === InvoiceStatus::Draft) {
            return;
        }

        if ($grandTotal > 0 && $amountPaid >= $grandTotal) {
            $invoice->forceFill([
                'status' => InvoiceStatus::Paid,
                'paid_at' => $invoice->paid_at ?? now(),
            ])->saveQuietly();

            return;
        }

        if ($amountPaid > 0) {
            if ($invoice->status !== InvoiceStatus::Overdue) {
                $invoice->forceFill(['status' => InvoiceStatus::PartiallyPaid])->saveQuietly();
            }

            return;
        }

        if (in_array($invoice->status, [InvoiceStatus::PartiallyPaid, InvoiceStatus::Paid], true)) {
            $invoice->forceFill(['status' => InvoiceStatus::Sent, 'paid_at' => null])->saveQuietly();
        }
    }
}
