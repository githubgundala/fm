<?php

namespace App\Services;

use App\Enums\DiscountType;
use App\Models\Quotation;

class QuotationCalculator
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
     * Recalculate cached totals for a quotation.
     */
    public function recalculate(Quotation $quotation): void
    {
        $subtotal = (float) $quotation->items()->sum('line_total');

        $totals = $this->calculate(
            $subtotal,
            $quotation->discount_type,
            (float) $quotation->discount_value,
            $quotation->tax_rate === null ? null : (float) $quotation->tax_rate,
        );

        $quotation->forceFill([
            'subtotal' => $totals['subtotal'],
            'tax_amount' => $totals['taxAmount'],
            'grand_total' => $totals['grandTotal'],
        ])->saveQuietly();
    }
}
