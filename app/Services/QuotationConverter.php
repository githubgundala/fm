<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Enums\QuotationStatus;
use App\Models\Invoice;
use App\Models\InvoiceSetting;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationConverter
{
    public function __construct(
        private InvoiceNumberGenerator $numbers,
        private InvoiceCalculator $invoiceCalculator,
        private QuotationCalculator $quotationCalculator,
    ) {}

    /**
     * Convert a quotation into a new invoice.
     *
     * One quotation may be converted many times (per termin/stage);
     * pass $itemIds to convert only selected items.
     *
     * @param  array<int>|null  $itemIds
     */
    public function convert(Quotation $quotation, ?array $itemIds = null, ?int $dueDays = null): Invoice
    {
        return DB::transaction(function () use ($quotation, $itemIds, $dueDays): Invoice {
            $quotation->loadMissing(['client', 'items']);

            $items = $itemIds === null
                ? $quotation->items
                : $quotation->items->whereIn('id', $itemIds);

            $settings = InvoiceSetting::cached();

            $invoice = new Invoice([
                'invoice_number' => $this->numbers->next(InvoiceNumberGenerator::TYPE_INVOICE),
                'client_id' => $quotation->client_id,
                'issue_date' => now()->format('Y-m-d'),
                'due_date' => now()->addDays($dueDays ?? $settings->default_due_days)->format('Y-m-d'),
                'status' => InvoiceStatus::Draft,
                'subtotal' => 0,
                'discount_type' => $quotation->discount_type,
                'discount_value' => $quotation->discount_value,
                'tax_rate' => $quotation->tax_rate,
                'tax_amount' => 0,
                'grand_total' => 0,
                'amount_paid' => 0,
                'amount_due' => 0,
                'currency' => $quotation->currency,
                'notes' => $quotation->notes,
                'terms' => $quotation->terms,
                'created_by' => auth()->id(),
            ]);
            $invoice->save();

            foreach ($items->values() as $index => $item) {
                $invoice->items()->create([
                    'description' => ($item->section ? "[{$item->section}] " : '').$item->description,
                    'qty' => $item->qty,
                    'unit_price' => $item->unit_price,
                    'sort_order' => $item->sort_order ?? $index,
                ]);
            }

            $this->invoiceCalculator->recalculate($invoice->refresh());

            $quotation->invoices()->syncWithoutDetaching([$invoice->id]);

            if ($quotation->status !== QuotationStatus::Converted) {
                $quotation->forceFill(['status' => QuotationStatus::Converted])->saveQuietly();
            }

            return $invoice->refresh();
        });
    }
}
