<?php

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Services\InvoiceCalculator;

it('recalculates totals when items change', function (): void {
    $invoice = Invoice::factory()->create();

    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 2, 'unit_price' => 100_000]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 50_000]);

    $invoice->refresh();

    expect((float) $invoice->subtotal)->toBe(250000.0)
        ->and((float) $invoice->grand_total)->toBe(250000.0)
        ->and((float) $invoice->amount_paid)->toBe(0.0)
        ->and((float) $invoice->amount_due)->toBe(250000.0);
});

it('keeps a partial payment as partially paid, not paid', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 1_000_000]);

    Payment::factory()->for($invoice, 'invoice')->create(['amount' => 300_000]);

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and((float) $invoice->amount_paid)->toBe(300000.0)
        ->and((float) $invoice->amount_due)->toBe(700000.0)
        ->and($invoice->paid_at)->toBeNull();
});

it('marks the invoice paid when settled in full', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 1_000_000]);

    Payment::factory()->for($invoice, 'invoice')->create(['amount' => 1_000_000]);

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $invoice->amount_due)->toBe(0.0)
        ->and($invoice->paid_at)->not->toBeNull();
});

it('never changes the status of a cancelled invoice', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Cancelled]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 1_000_000]);

    Payment::factory()->for($invoice, 'invoice')->create(['amount' => 1_000_000]);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Cancelled);
});

it('syncs line totals from qty and unit price', function (): void {
    $invoice = Invoice::factory()->create();

    $item = InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 3, 'unit_price' => 150_000]);

    expect((float) $item->refresh()->line_total)->toBe(450000.0);
});

it('exposes the calculator through the container', function (): void {
    expect(app(InvoiceCalculator::class))->toBeInstanceOf(InvoiceCalculator::class);
});
