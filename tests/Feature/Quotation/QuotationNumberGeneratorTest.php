<?php

use App\Models\InvoiceSetting;
use App\Services\QuotationNumberGenerator;
use Illuminate\Support\Facades\Cache;

beforeEach(function (): void {
    Cache::forget(InvoiceSetting::CACHE_KEY);
    InvoiceSetting::cached()->update(['quotation_prefix' => 'Q']);
});

it('generates monthly quotation numbers', function (): void {
    $generator = app(QuotationNumberGenerator::class);
    $year = (int) now()->format('Y');
    $month = now()->format('m');

    expect($generator->next())->toBe("Q/{$year}/{$month}/001")
        ->and($generator->next())->toBe("Q/{$year}/{$month}/002");
});

it('resets the sequence every month', function (): void {
    $generator = app(QuotationNumberGenerator::class);

    expect($generator->next(2026, 7))->toBe('Q/2026/07/001')
        ->and($generator->next(2026, 7))->toBe('Q/2026/07/002')
        ->and($generator->next(2026, 8))->toBe('Q/2026/08/001');
});

it('never generates duplicate numbers', function (): void {
    $generator = app(QuotationNumberGenerator::class);

    $numbers = collect(range(1, 10))
        ->map(fn (): string => $generator->next())
        ->all();

    expect($numbers)->toHaveCount(10)
        ->and(array_unique($numbers))->toHaveCount(10);
});
