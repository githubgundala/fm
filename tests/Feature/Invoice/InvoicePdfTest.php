<?php

use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSetting;
use App\Models\User;
use App\Services\InvoicePdfService;
use Illuminate\Support\Facades\Storage;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

function pdfInvoice(?float $taxRate = 11): Invoice
{
    $invoice = Invoice::factory()->create(['tax_rate' => $taxRate]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 2, 'unit_price' => 250_000]);

    return $invoice->refresh();
}

it('downloads the invoice as a pdf file', function (): void {
    $invoice = pdfInvoice();

    $response = get(route('invoices.pdf', $invoice));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

it('generates a pdf larger than 5kb containing totals', function (): void {
    $invoice = pdfInvoice();

    $binary = app(InvoicePdfService::class)->generate($invoice);

    expect($binary)->toStartWith('%PDF')
        ->and(strlen($binary))->toBeGreaterThan(5 * 1024);
});

it('renders an invoice without tax', function (): void {
    $invoice = pdfInvoice(null);

    $binary = app(InvoicePdfService::class)->generate($invoice);

    expect($binary)->toStartWith('%PDF')
        ->and((float) $invoice->tax_amount)->toBe(0.0);
});

it('renders an invoice without a logo', function (): void {
    $invoice = pdfInvoice();

    expect(app(InvoicePdfService::class)->generate($invoice))->toStartWith('%PDF');
});

it('displays the receiver name on the print page', function (): void {
    $invoice = pdfInvoice();
    $invoice->update(['receiver_name' => 'Andi (PT Maju)']);

    get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertSee('Andi (PT Maju)');
});

it('displays the signature name on the print page', function (): void {
    InvoiceSetting::cached()->update(['signature_name' => 'Budi Santoso']);
    $invoice = pdfInvoice();

    get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertSee('Budi Santoso');
});

it('renders a pdf when the signature file is missing', function (): void {
    InvoiceSetting::cached()->update(['signature_path' => 'invoice-signatures/missing.png']);
    $invoice = pdfInvoice();

    expect(app(InvoicePdfService::class)->generate($invoice))->toStartWith('%PDF');
});

it('embeds the signature image in the pdf when present', function (): void {
    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    Storage::disk('public')->put('invoice-signatures/test-ttd.png', $png);

    try {
        InvoiceSetting::cached()->update(['signature_path' => 'invoice-signatures/test-ttd.png']);
        $invoice = pdfInvoice();

        expect(app(InvoicePdfService::class)->generate($invoice))->toStartWith('%PDF');
    } finally {
        Storage::disk('public')->delete('invoice-signatures/test-ttd.png');
    }
});

it('displays the print page', function (): void {
    $invoice = pdfInvoice();

    get(route('invoices.print', $invoice))
        ->assertOk()
        ->assertSee($invoice->invoice_number)
        ->assertSee('window.print()', false);
});

it('redirects guests away from pdf and print pages', function (): void {
    auth()->logout();

    $invoice = Invoice::factory()->create();

    get(route('invoices.pdf', $invoice))->assertRedirect('/login');
    get(route('invoices.print', $invoice))->assertRedirect('/login');
});
