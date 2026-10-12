<?php

use App\Models\Quotation;
use App\Models\QuotationItem;
use App\Models\User;
use App\Services\QuotationPdfService;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
});

function pdfQuotation(?float $taxRate = 11): Quotation
{
    $quotation = Quotation::factory()->create(['tax_rate' => $taxRate, 'title' => 'Sistem ERP — Procure-to-Pay']);
    QuotationItem::factory()->for($quotation, 'quotation')->create(['section' => 'Scope', 'qty' => 1, 'unit_price' => 18_000_000]);

    return $quotation->refresh();
}

it('downloads the quotation as a pdf file', function (): void {
    $quotation = pdfQuotation();

    $response = get(route('quotations.pdf', $quotation));

    $response->assertOk();
    expect($response->headers->get('Content-Type'))->toBe('application/pdf')
        ->and($response->getContent())->toStartWith('%PDF');
});

it('generates a pdf larger than 5kb', function (): void {
    $quotation = pdfQuotation();

    $binary = app(QuotationPdfService::class)->generate($quotation);

    expect($binary)->toStartWith('%PDF')
        ->and(strlen($binary))->toBeGreaterThan(5 * 1024);
});

it('renders a quotation without tax', function (): void {
    $quotation = pdfQuotation(null);

    $binary = app(QuotationPdfService::class)->generate($quotation);

    expect($binary)->toStartWith('%PDF')
        ->and((float) $quotation->tax_amount)->toBe(0.0);
});

it('displays the quotation on the print page', function (): void {
    $quotation = pdfQuotation();

    get(route('quotations.print', $quotation))
        ->assertOk()
        ->assertSee($quotation->quotation_number)
        ->assertSee('window.print()', false);
});

it('redirects guests away from pdf and print pages', function (): void {
    auth()->logout();

    $quotation = Quotation::factory()->create();

    get(route('quotations.pdf', $quotation))->assertRedirect('/login');
    get(route('quotations.print', $quotation))->assertRedirect('/login');
});
