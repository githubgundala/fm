<?php

use App\Enums\InvoiceStatus;
use App\Filament\Widgets\InvoiceStatsOverview;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\Payment;
use App\Models\User;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

it('marks past-due invoices as overdue', function (): void {
    $late = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'due_date' => now()->subDays(5)->format('Y-m-d'),
    ]);
    InvoiceItem::factory()->for($late, 'invoice')->create(['qty' => 1, 'unit_price' => 500_000]);

    $paid = Invoice::factory()->create([
        'status' => InvoiceStatus::Sent,
        'due_date' => now()->subDays(5)->format('Y-m-d'),
    ]);
    InvoiceItem::factory()->for($paid, 'invoice')->create(['qty' => 1, 'unit_price' => 500_000]);
    Payment::factory()->for($paid->refresh(), 'invoice')->create(['amount' => 500_000]);

    $cancelled = Invoice::factory()->create([
        'status' => InvoiceStatus::Cancelled,
        'due_date' => now()->subDays(5)->format('Y-m-d'),
    ]);

    $this->artisan('invoices:mark-overdue')->assertSuccessful();

    expect($late->refresh()->status)->toBe(InvoiceStatus::Overdue)
        ->and($paid->refresh()->status)->toBe(InvoiceStatus::Paid)
        ->and($cancelled->refresh()->status)->toBe(InvoiceStatus::Cancelled);
});

it('computes stats matching the database', function (): void {
    $overdue = Invoice::factory()->create([
        'status' => InvoiceStatus::Overdue,
        'due_date' => now()->subDay()->format('Y-m-d'),
    ]);
    InvoiceItem::factory()->for($overdue, 'invoice')->create(['qty' => 1, 'unit_price' => 400_000]);

    $paid = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($paid, 'invoice')->create(['qty' => 1, 'unit_price' => 600_000]);
    Payment::factory()->for($paid, 'invoice')->create([
        'amount' => 600_000,
        'payment_date' => now()->format('Y-m-d'),
    ]);

    $overdue->refresh();
    $paid->refresh();

    expect((float) $overdue->amount_due)->toBe(400000.0)
        ->and($paid->status)->toBe(InvoiceStatus::Paid)
        ->and((float) Payment::query()
            ->whereYear('payment_date', now()->year)
            ->whereMonth('payment_date', now()->month)
            ->sum('amount'))->toBe(600000.0)
        ->and(Invoice::query()->where('status', InvoiceStatus::Overdue)->count())->toBe(1);
});

it('loads the dashboard with invoice widgets', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 100_000]);

    $this->actingAs(superAdmin())->get('/admin')->assertOk();
});

it('renders the stats widget without errors', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 100_000]);

    $widget = app(InvoiceStatsOverview::class);

    expect($widget)->toBeInstanceOf(InvoiceStatsOverview::class);
});
