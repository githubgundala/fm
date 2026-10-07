<?php

namespace App\Services;

use App\Enums\InvoiceStatus;
use App\Models\Invoice;
use Illuminate\Support\Facades\DB;

class InvoiceDuplicator
{
    public function __construct(
        private InvoiceNumberGenerator $numbers,
        private InvoiceCalculator $calculator,
    ) {}

    /**
     * Duplicate an invoice with a fresh number, draft status, and copied items.
     */
    public function duplicate(Invoice $invoice): Invoice
    {
        return DB::transaction(function () use ($invoice): Invoice {
            $invoice->loadMissing('items');

            $copy = $invoice->replicate([
                'invoice_number',
                'status',
                'sent_at',
                'paid_at',
                'amount_paid',
                'amount_due',
                'created_by',
            ]);
            $copy->invoice_number = $this->numbers->next(InvoiceNumberGenerator::TYPE_INVOICE);
            $copy->status = InvoiceStatus::Draft;
            $copy->sent_at = null;
            $copy->paid_at = null;

            $createdBy = auth()->id();
            $copy->created_by = is_int($createdBy) ? $createdBy : null;
            $copy->save();

            foreach ($invoice->items as $item) {
                $itemCopy = $item->replicate(['invoice_id']);
                $itemCopy->invoice_id = $copy->id;
                $itemCopy->save();
            }

            $this->calculator->recalculate($copy->refresh());

            return $copy->refresh();
        });
    }
}
