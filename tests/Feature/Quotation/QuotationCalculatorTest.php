<?php

use App\Enums\DiscountType;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Services\QuotationCalculator;

function quotationWithItems(?float $taxRate = 11): Quotation
{
    $quotation = Quotation::factory()->create(['tax_rate' => $taxRate, 'discount_type' => null, 'discount_value' => 0]);
    QuotationItem::factory()->for($quotation, 'quotation')->create(['qty' => 2, 'unit_price' => 1_000_000]);

    return $quotation->refresh();
}

it('calculates subtotal, tax 11%, and grand total', function (): void {
    $quotation = quotationWithItems(11);

    expect((float) $quotation->subtotal)->toBe(2000000.0)
        ->and((float) $quotation->tax_amount)->toBe(220000.0)
        ->and((float) $quotation->grand_total)->toBe(2220000.0);
});

it('calculates percent discount before tax', function (): void {
    $totals = app(QuotationCalculator::class)->calculate(1_000_000, DiscountType::Percent, 10, 11);

    expect($totals['discountAmount'])->toBe(100000.0)
        ->and($totals['taxAmount'])->toBe(99000.0)
        ->and($totals['grandTotal'])->toBe(999000.0);
});

it('caps fixed discount at subtotal', function (): void {
    $totals = app(QuotationCalculator::class)->calculate(500_000, DiscountType::Fixed, 900_000, 11);

    expect($totals['discountAmount'])->toBe(500000.0)
        ->and($totals['grandTotal'])->toBe(0.0);
});

it('supports quotations without tax', function (): void {
    $quotation = quotationWithItems(null);

    expect((float) $quotation->tax_amount)->toBe(0.0)
        ->and((float) $quotation->grand_total)->toBe((float) $quotation->subtotal);
});

it('recalculates totals when an item is added', function (): void {
    $quotation = quotationWithItems(11);
    $before = (float) $quotation->grand_total;

    QuotationItem::factory()->for($quotation, 'quotation')->create(['qty' => 1, 'unit_price' => 1_000_000]);

    expect((float) $quotation->refresh()->grand_total)->toBeGreaterThan($before);
});
