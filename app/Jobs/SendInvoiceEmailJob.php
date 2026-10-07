<?php

namespace App\Jobs;

use App\Enums\InvoiceStatus;
use App\Mail\InvoiceMail;
use App\Models\Invoice;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Mail;

class SendInvoiceEmailJob implements ShouldQueue
{
    use Queueable;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public Invoice $invoice,
        public string $to,
        public ?string $cc,
        public string $subject,
        public string $body,
    ) {}

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $mail = Mail::to($this->to);

        if ($this->cc) {
            $mail->cc($this->cc);
        }

        $mail->send(new InvoiceMail($this->invoice, $this->subject, $this->body));

        $invoice = $this->invoice->refresh();

        if ($invoice->status === InvoiceStatus::Draft) {
            $invoice->forceFill([
                'status' => InvoiceStatus::Sent,
                'sent_at' => $invoice->sent_at ?? now(),
            ])->save();
        }
    }

    /**
     * Get the number of times the job may be attempted.
     */
    public function tries(): int
    {
        return 3;
    }

    /**
     * Get the number of seconds to wait before retrying the job.
     *
     * @return array<int, int>
     */
    public function backoff(): array
    {
        return [60, 60, 60];
    }
}
