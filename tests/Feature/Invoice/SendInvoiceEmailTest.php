<?php

use App\Enums\InvoiceStatus;
use App\Filament\Resources\Invoices\Pages\ListInvoices;
use App\Jobs\SendInvoiceEmailJob;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use App\Models\InvoiceItem;
use App\Support\InvoiceTemplate;
use Filament\Actions\Testing\TestAction;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Queue;

use function Pest\Livewire\livewire;

beforeEach(function (): void {
    $this->actingAs(superAdmin());
});

function emailableInvoice(): Invoice
{
    $invoice = Invoice::factory()->create(['status' => InvoiceStatus::Draft]);
    InvoiceItem::factory()->for($invoice, 'invoice')->create(['qty' => 1, 'unit_price' => 750_000]);

    return $invoice->refresh()->load('client');
}

it('sends the invoice email with a pdf attachment', function (): void {
    Mail::fake();

    $invoice = emailableInvoice();
    $defaults = InvoiceTemplate::defaults($invoice);

    SendInvoiceEmailJob::dispatchSync(
        $invoice,
        $invoice->client->email,
        null,
        $defaults['subject'],
        $defaults['body'],
    );

    Mail::assertQueued(InvoiceMail::class, function (InvoiceMail $mail) use ($invoice): bool {
        $attachments = $mail->attachments();

        return $mail->hasTo($invoice->client->email)
            && count($attachments) === 1
            && str_ends_with($attachments[0]->as, $invoice->invoice_number.'.pdf');
    });
});

it('marks a draft invoice as sent after emailing', function (): void {
    Mail::fake();

    $invoice = emailableInvoice();
    $defaults = InvoiceTemplate::defaults($invoice);

    SendInvoiceEmailJob::dispatchSync($invoice, $invoice->client->email, null, $defaults['subject'], $defaults['body']);

    $invoice->refresh();

    expect($invoice->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->sent_at)->not->toBeNull();
});

it('keeps an already sent invoice untouched', function (): void {
    Mail::fake();

    $invoice = emailableInvoice();
    $invoice->forceFill(['status' => InvoiceStatus::Sent, 'sent_at' => now()->subDay()])->save();
    $sentAt = $invoice->sent_at;

    $defaults = InvoiceTemplate::defaults($invoice);

    SendInvoiceEmailJob::dispatchSync($invoice, $invoice->client->email, null, $defaults['subject'], $defaults['body']);

    expect($invoice->refresh()->status)->toBe(InvoiceStatus::Sent)
        ->and($invoice->sent_at->equalTo($sentAt))->toBeTrue();
});

it('dispatches the email job from the table action', function (): void {
    Queue::fake();

    $invoice = emailableInvoice();

    livewire(ListInvoices::class)
        ->callAction(TestAction::make('sendEmail')->table($invoice), [
            'to' => $invoice->client->email,
            'cc' => null,
            'subject' => 'Subjek tes',
            'body' => 'Isi tes',
        ])
        ->assertNotified();

    Queue::assertPushed(SendInvoiceEmailJob::class);
});

it('renders the email body with the invoice summary', function (): void {
    $invoice = emailableInvoice();
    $defaults = InvoiceTemplate::defaults($invoice);

    $html = (new InvoiceMail($invoice, $defaults['subject'], $defaults['body']))->render();

    expect($html)->toContain($invoice->invoice_number)
        ->and($html)->toContain('Sisa Tagihan');
});

it('replaces every template placeholder', function (): void {
    $invoice = emailableInvoice();

    $subject = InvoiceTemplate::render('Tagihan {invoice_number} untuk {client_name} dari {company_name}', $invoice);
    $body = InvoiceTemplate::render('Sisa {amount_due}, tempo {due_date}', $invoice);

    expect($subject)->not->toContain('{')
        ->and($subject)->toContain($invoice->invoice_number)
        ->and($subject)->toContain($invoice->client->name)
        ->and($body)->not->toContain('{')
        ->and($body)->toContain($invoice->due_date->format('d M Y'));
});
