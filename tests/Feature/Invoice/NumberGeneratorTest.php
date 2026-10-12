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

it('peeks the next number without consuming it', function (): void {
    $generator = app(InvoiceNumberGenerator::class);
    $year = (int) now()->format('Y');

    expect($generator->peek())->toBe("INV-{$year}-0001")
        ->and($generator->peek())->toBe("INV-{$year}-0001")
        ->and($generator->next(InvoiceNumberGenerator::TYPE_INVOICE))->toBe("INV-{$year}-0001")
        ->and($generator->peek())->toBe("INV-{$year}-0002");
});

it('syncs the counter past a manually entered number', function (): void {
    $generator = app(InvoiceNumberGenerator::class);
    $year = (int) now()->format('Y');

    $generator->syncCounter("INV-{$year}-0099");

    expect($generator->peek())->toBe("INV-{$year}-0100")
        ->and($generator->next(InvoiceNumberGenerator::TYPE_INVOICE))->toBe("INV-{$year}-0100");
});

it('ignores manual numbers outside the current sequence', function (): void {
    $generator = app(InvoiceNumberGenerator::class);
    $year = (int) now()->format('Y');

    $generator->syncCounter('CUSTOM-001');
    $generator->syncCounter("INV-{$year}-0001");

    expect($generator->peek())->toBe("INV-{$year}-0002");
});
