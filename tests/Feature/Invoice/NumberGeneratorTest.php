<?php

use App\Models\InvoiceSetting;
use App\Services\InvoiceNumberGenerator;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(InvoiceSetting::CACHE_KEY);
});

it('generates sequential invoice numbers', function (): void {
    $generator = app(InvoiceNumberGenerator::class);
    $year = (int) now()->format('Y');

    expect($generator->next(InvoiceNumberGenerator::TYPE_INVOICE))->toBe("INV-{$year}-0001")
        ->and($generator->next(InvoiceNumberGenerator::TYPE_INVOICE))->toBe("INV-{$year}-0002");
});

it('uses a separate sequence for payments', function (): void {
    $generator = app(InvoiceNumberGenerator::class);
    $year = (int) now()->format('Y');

    expect($generator->next(InvoiceNumberGenerator::TYPE_PAYMENT))->toBe("PAY-{$year}-0001")
        ->and($generator->next(InvoiceNumberGenerator::TYPE_INVOICE))->toBe("INV-{$year}-0001");
});

it('resets the sequence for a new year', function (): void {
    $generator = app(InvoiceNumberGenerator::class);

    expect($generator->next(InvoiceNumberGenerator::TYPE_INVOICE, 2025))->toBe('INV-2025-0001')
        ->and($generator->next(InvoiceNumberGenerator::TYPE_INVOICE, 2025))->toBe('INV-2025-0002')
        ->and($generator->next(InvoiceNumberGenerator::TYPE_INVOICE, 2026))->toBe('INV-2026-0001');
});

it('never generates duplicate numbers', function (): void {
    $generator = app(InvoiceNumberGenerator::class);

    $numbers = collect(range(1, 10))
        ->map(fn (): string => $generator->next(InvoiceNumberGenerator::TYPE_INVOICE))
        ->all();

    expect($numbers)->toHaveCount(10)
        ->and(array_unique($numbers))->toHaveCount(10);
});
