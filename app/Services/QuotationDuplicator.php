<?php

namespace App\Services;

use App\Enums\QuotationStatus;
use App\Models\Quotation;
use Illuminate\Support\Facades\DB;

class QuotationDuplicator
{
    public function __construct(
        private QuotationNumberGenerator $numbers,
        private QuotationCalculator $calculator,
    ) {}

    /**
     * Duplicate a quotation with a fresh number and draft status.
     */
    public function duplicate(Quotation $quotation): Quotation
    {
        return DB::transaction(function () use ($quotation): Quotation {
            $quotation->loadMissing('items');

            $copy = $quotation->replicate([
                'quotation_number',
                'status',
                'sent_at',
                'approved_at',
                'created_by',
            ]);
            $copy->quotation_number = $this->numbers->next();
            $copy->status = QuotationStatus::Draft;
            $copy->sent_at = null;
            $copy->approved_at = null;

            $createdBy = auth()->id();
            $copy->created_by = is_int($createdBy) ? $createdBy : null;
            $copy->save();

            foreach ($quotation->items as $item) {
                $itemCopy = $item->replicate(['quotation_id']);
                $itemCopy->quotation_id = $copy->id;
                $itemCopy->save();
            }

            $this->calculator->recalculate($copy->refresh());

            return $copy->refresh();
        });
    }
}
