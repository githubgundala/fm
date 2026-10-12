<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\InvoiceResource;
use App\Filament\Resources\Invoices\Pages\CreateInvoice;
use App\Filament\Resources\Invoices\Pages\EditInvoice;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Models\Client;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Services\InvoiceDuplicator;
use App\Services\InvoiceNumberGenerator;

use function Pest\Laravel\assertDatabaseHas;
use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(superAdmin());
});

it('creates an invoice with calculated totals', function (): void {
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'items' => [
                ['description' => 'Jasa A', 'qty' => 2, 'unit_price' => 100000],
                ['description' => 'Jasa B', 'qty' => 1, 'unit_price' => 50000],
            ],
            'discount_type' => null,
            'discount_value' => 0,
            'tax_rate' => null,
            'currency' => 'IDR',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors()
        ->assertRedirect();

    $invoice = Invoice::where('client_id', $client->id)->first();

    expect($invoice)->not->toBeNull()
        ->and($invoice->invoice_number)->toStartWith('INV-')
        ->and($invoice->status)->toBe(InvoiceStatus::Draft)
        ->and((float) $invoice->subtotal)->toBe(250000.0)
        ->and((float) $invoice->grand_total)->toBe(250000.0)
        ->and($invoice->items)->toHaveCount(2);
});

it('rejects an invoice without items', function (): void {
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'items' => [],
        ])
        ->call('create')
        ->assertHasFormErrors(['items'])
        ->assertNotNotified();

    expect(Invoice::count())->toBe(0);
});

it('rejects a due date before the issue date', function (): void {
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'issue_date' => '2026-10-10',
            'due_date' => '2026-10-05',
            'items' => [
                ['description' => 'Jasa A', 'qty' => 1, 'unit_price' => 100000],
            ],
        ])
        ->call('create')
        ->assertHasFormErrors(['due_date']);

    expect(Invoice::count())->toBe(0);
});

it('saves the receiver name on create', function (): void {
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'items' => [
                ['description' => 'Jasa A', 'qty' => 1, 'unit_price' => 100000],
            ],
            'currency' => 'IDR',
            'receiver_name' => 'Andi (PT Maju)',
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    assertDatabaseHas(Invoice::class, ['client_id' => $client->id, 'receiver_name' => 'Andi (PT Maju)']);
});

it('redirects away when editing a sent invoice', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create();

    livewire(EditInvoice::class, ['record' => $invoice->id])
        ->assertRedirect(InvoiceResource::getUrl('view', ['record' => $invoice]));
});

it('lists invoice records in the table', function (): void {
    $invoices = Invoice::factory()->count(3)->create();

    livewire(ListInvoices::class)
        ->assertCanSeeTableRecords($invoices)
        ->searchTable($invoices->first()->invoice_number)
        ->assertCanSeeTableRecords($invoices->take(1))
        ->assertCanNotSeeTableRecords($invoices->skip(1));
});

it('duplicates an invoice with a fresh number', function (): void {
    $invoice = Invoice::factory()->create();
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 200000]);

    $copy = app(InvoiceDuplicator::class)->duplicate($invoice->refresh());

    expect($copy->id)->not->toBe($invoice->id)
        ->and($copy->invoice_number)->not->toBe($invoice->invoice_number)
        ->and($copy->status)->toBe(InvoiceStatus::Draft)
        ->and($copy->items)->toHaveCount(1)
        ->and((float) $copy->grand_total)->toBe(200000.0);

    assertDatabaseHas(Invoice::class, ['invoice_number' => $copy->invoice_number]);
});

it('prefills the generated number on create but allows editing it', function (): void {
    $client = Client::factory()->create();
    $year = (int) now()->format('Y');

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'items' => [
                ['description' => 'Jasa A', 'qty' => 1, 'unit_price' => 100000],
            ],
            'currency' => 'IDR',
            'invoice_number' => "INV-{$year}-0099",
        ])
        ->call('create')
        ->assertNotified()
        ->assertHasNoFormErrors();

    assertDatabaseHas(Invoice::class, ['client_id' => $client->id, 'invoice_number' => "INV-{$year}-0099"]);

    // Counter synced past the manual number, so the next auto number cannot collide.
    expect(app(InvoiceNumberGenerator::class)->peek())->toBe("INV-{$year}-0100");
});

it('rejects a duplicate invoice number on create', function (): void {
    $existing = Invoice::factory()->create();
    $client = Client::factory()->create();

    livewire(CreateInvoice::class)
        ->fillForm([
            'client_id' => $client->id,
            'issue_date' => '2026-10-01',
            'due_date' => '2026-10-15',
            'items' => [
                ['description' => 'Jasa A', 'qty' => 1, 'unit_price' => 100000],
            ],
            'currency' => 'IDR',
            'invoice_number' => $existing->invoice_number,
        ])
        ->call('create')
        ->assertHasFormErrors(['invoice_number']);

    expect(Invoice::count())->toBe(1);
});
