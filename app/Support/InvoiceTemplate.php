<?php

namespace App\Support;

use App\Models\Invoice;
use App\Models\InvoiceSetting;

class InvoiceTemplate
{
    /**
     * Replace placeholders in email subject/body templates.
     *
     * Supported: {invoice_number}, {client_name}, {company_name}, {amount_due}, {due_date}
     */
    public static function render(string $template, Invoice $invoice): string
    {
        $settings = InvoiceSetting::cached();

        return strtr($template, [
            '{invoice_number}' => $invoice->invoice_number,
            '{client_name}' => $invoice->client->name,
            '{company_name}' => $settings->company_name,
            '{amount_due}' => 'Rp '.number_format((float) $invoice->amount_due, 2, ',', '.'),
            '{due_date}' => $invoice->due_date->format('d M Y'),
        ]);
    }

    /**
     * Default subject and body for an invoice, rendered from settings.
     *
     * @return array{subject: string, body: string}
     */
    public static function defaults(Invoice $invoice): array
    {
        $settings = InvoiceSetting::cached();

        return [
            'subject' => self::render($settings->email_subject_template, $invoice),
            'body' => self::render($settings->email_body_template ?? '', $invoice),
        ];
    }
}
