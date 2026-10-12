<?php

use App\Enums\InvoiceStatus;
use App\Enums\PaymentMethod;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Models\InvoiceSetting;
use App\Models\Payment;
use App\Models\User;
use App\Services\PaymentRecorder;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

use function Pest\Laravel\get;

beforeEach(function (): void {
    $this->actingAs(User::factory()->create());
    Cache::forget(InvoiceSetting::CACHE_KEY);
});

function payableInvoice(): Invoice
{
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Sent]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 1_000_000]);

    return $invoice->refresh();
}

it('records a down payment as partially paid', function (): void {
    $invoice = payableInvoice();

    $payment = app(PaymentRecorder::class)->record($invoice, 300_000, PaymentMethod::Transfer, notes: 'DP 30%');

    expect($payment->payment_number)->toStartWith('PAY-')
        ->and($invoice->refresh()->status)->toBe(InvoiceStatus::PartiallyPaid)
        ->and((float) $invoice->amount_due)->toBe(700000.0);
});

it('marks the invoice paid when the balance is settled', function (): void {
    $invoice = payableInvoice();

    app(PaymentRecorder::class)->record($invoice, 300_000, 'transfer');
    app(PaymentRecorder::class)->record($invoice, 700_000, 'qris');

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Paid)
        ->and($invoice->paid_at)->not->toBeNull()
        ->and((float) $invoice->amount_due)->toBe(0.0);
});

it('rejects overpayment when it is not allowed', function (): void {
    $invoice = payableInvoice();

    app(PaymentRecorder::class)->record($invoice, 1_500_000, 'cash');
})->throws(ValidationException::class);

it('allows overpayment when the setting permits it', function (): void {
    $invoice = payableInvoice();
    InvoiceSetting::cached()->update(['allow_overpayment' => true]);

    $payment = app(PaymentRecorder::class)->record($invoice, 1_200_000, 'cash');

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Paid)
        ->and((float) $payment->amount)->toBe(1200000.0);
});

it('rejects payments to draft invoices', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 100_000]);

    app(PaymentRecorder::class)->record($invoice->refresh(), 50_000, 'cash');
})->throws(ValidationException::class);

it('rejects payments to cancelled invoices', function (): void {
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Cancelled]);

    app(PaymentRecorder::class)->record($invoice, 50_000, 'cash');
})->throws(ValidationException::class);

it('accepts the method as an enum instance from Filament forms', function (): void {
    $invoice = payableInvoice();

    $payment = app(PaymentRecorder::class)->record($invoice, 300_000, PaymentMethod::Cash);

    expect($payment->method)->toBe(PaymentMethod::Cash);
});

it('rejects an empty payment method with a validation error', function (): void {
    $invoice = payableInvoice();

    app(PaymentRecorder::class)->record($invoice, 50_000, '');
})->throws(ValidationException::class);

it('displays the payment slip', function (): void {
    $invoice = payableInvoice();
    $payment = Payment::factory()->for($invoice, 'invoice')->create(['amount' => 300_000]);

    get(route('payments.slip', $payment))
        ->assertOk()
        ->assertSee($payment->payment_number)
        ->assertSee($invoice->invoice_number);
});

it('displays the slip receiver name from settings', function (): void {
    InvoiceSetting::cached()->update(['slip_receiver_name' => 'Sari Kasir']);
    $invoice = payableInvoice();
    $payment = Payment::factory()->for($invoice, 'invoice')->create(['amount' => 300_000]);

    get(route('payments.slip', $payment))
        ->assertOk()
        ->assertSee('Sari Kasir');
});

it('displays the signature image on the slip when configured', function (): void {
    Storage::disk('public')->put('invoice-signatures/ttd.png', 'fake-png');
    InvoiceSetting::cached()->update(['signature_path' => 'invoice-signatures/ttd.png']);
    $invoice = payableInvoice();
    $payment = Payment::factory()->for($invoice, 'invoice')->create(['amount' => 300_000]);

    get(route('payments.slip', $payment))
        ->assertOk()
        ->assertSee('alt="Tanda tangan"', false);
});

it('redirects guests away from the payment slip', function (): void {
    auth()->logout();

    $payment = Payment::factory()->create();

    get(route('payments.slip', $payment))->assertRedirect('/login');
});
