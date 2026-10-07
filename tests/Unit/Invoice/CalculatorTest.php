<?php

use App\Enums\DiscountType;
use App\Services\InvoiceCalculator;

beforeEach(function (): void {
    $this->calculator = new InvoiceCalculator;
});

it('calculates totals without discount or tax', function (): void {
    expect($this->calculator->calculate(1_000_000, null, 0, null))->toBe([
        'subtotal' => 1000000.0,
        'discountAmount' => 0.0,
        'taxAmount' => 0.0,
        'grandTotal' => 1000000.0,
    ]);
});

it('calculates fixed discount', function (): void {
    $result = $this->calculator->calculate(1_000_000, DiscountType::Fixed, 200_000, null);

    expect($result['discountAmount'])->toBe(200000.0)
        ->and($result['grandTotal'])->toBe(800000.0);
});

it('caps fixed discount at subtotal', function (): void {
    $result = $this->calculator->calculate(100_000, DiscountType::Fixed, 500_000, null);

    expect($result['discountAmount'])->toBe(100000.0)
        ->and($result['grandTotal'])->toBe(0.0);
});

it('calculates percent discount', function (): void {
    $result = $this->calculator->calculate(1_000_000, DiscountType::Percent, 10, null);

    expect($result['discountAmount'])->toBe(100000.0)
        ->and($result['grandTotal'])->toBe(900000.0);
});

it('calculates tax on the discounted amount', function (): void {
    $result = $this->calculator->calculate(1_000_000, DiscountType::Percent, 10, 11);

    // taxable = 900.000, tax = 99.000, grand = 999.000
    expect($result['taxAmount'])->toBe(99000.0)
        ->and($result['grandTotal'])->toBe(999000.0);
});

it('treats null tax rate as zero tax', function (): void {
    $result = $this->calculator->calculate(500_000, null, 0, null);

    expect($result['taxAmount'])->toBe(0.0)
        ->and($result['grandTotal'])->toBe(500000.0);
});
