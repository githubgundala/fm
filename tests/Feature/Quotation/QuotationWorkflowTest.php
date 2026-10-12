<?php

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Services\QuotationConverter;
use App\Services\QuotationDuplicator;

use function Pest\Laravel\artisan;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

function convertibleQuotation(): Quotation
{
    $quotation = Quotation::factory()->create(['status' => QuotationStatus::Approved]);
    QuotationItem::factory()->for($quotation, 'quotation')->create(['section' => 'Scope', 'qty' => 1, 'unit_price' => 5_000_000]);

    return $quotation->refresh();
}

it('converts a quotation into an invoice', function (): void {
    $quotation = convertibleQuotation();

    $invoice = app(QuotationConverter::class)->convert($quotation);

    expect($invoice->client_id)->toBe($quotation->client_id)
        ->and($invoice->items)->toHaveCount(1)
        ->and((float) $invoice->grand_total)->toBe((float) $quotation->grand_total)
        ->and($quotation->refresh()->status)->toBe(QuotationStatus::Converted)
        ->and($quotation->invoices)->toHaveCount(1);
});

it('converts one quotation into many invoices', function (): void {
    $quotation = convertibleQuotation();
    $converter = app(QuotationConverter::class);

    $first = $converter->convert($quotation);
    $second = $converter->convert($quotation);

    expect($first->invoice_number)->not->toBe($second->invoice_number)
        ->and($quotation->refresh()->invoices)->toHaveCount(2);
});

it('converts only selected items', function (): void {
    $quotation = convertibleQuotation();
    QuotationItem::factory()->for($quotation, 'quotation')->create(['section' => 'Termin 2', 'qty' => 1, 'unit_price' => 1_000_000]);
    $quotation->refresh();

    $firstItem = $quotation->items->first();

    $invoice = app(QuotationConverter::class)->convert($quotation, [$firstItem->id]);

    expect($invoice->items)->toHaveCount(1)
        ->and($invoice->items->first()->description)->toContain($firstItem->description);
});

it('duplicates a quotation with a fresh number and draft status', function (): void {
    $quotation = convertibleQuotation();

    $copy = app(QuotationDuplicator::class)->duplicate($quotation);

    expect($copy->quotation_number)->not->toBe($quotation->quotation_number)
        ->and($copy->status)->toBe(QuotationStatus::Draft)
        ->and($copy->items)->toHaveCount(1);
});

it('marks overdue quotations as expired via command', function (): void {
    $expired = Quotation::factory()->create([
        'status' => QuotationStatus::Sent,
        'issue_date' => now()->subDays(10)->format('Y-m-d'),
        'valid_until' => now()->subDay()->format('Y-m-d'),
    ]);
    $fresh = Quotation::factory()->create([
        'status' => QuotationStatus::Sent,
        'issue_date' => now()->format('Y-m-d'),
        'valid_until' => now()->addDays(3)->format('Y-m-d'),
    ]);

    artisan('quotations:mark-expired')->assertSuccessful();

    expect($expired->refresh()->status)->toBe(QuotationStatus::Expired)
        ->and($fresh->refresh()->status)->toBe(QuotationStatus::Sent);
});
