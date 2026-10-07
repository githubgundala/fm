<?php

namespace App\Services;

use App\Models\InvoiceSetting;
use App\Models\NumberCounter;
use Illuminate\Support\Facades\DB;

class InvoiceNumberGenerator
{
    public const TYPE_INVOICE = 'invoice';

    public const TYPE_PAYMENT = 'payment';

    /**
     * Generate the next document number, safe for concurrent requests.
     */
    public function next(string $type, ?int $year = null): string
    {
        $settings = InvoiceSetting::cached();
        $year ??= (int) now()->format('Y');

        $prefix = $type === self::TYPE_PAYMENT ? $settings->payment_prefix : $settings->invoice_prefix;
        $counterYear = $settings->yearly_reset ? $year : 0;

        $sequence = DB::transaction(function () use ($type, $counterYear): int {
            $counter = NumberCounter::query()
                ->where('type', $type)
                ->where('year', $counterYear)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = NumberCounter::create(['type' => $type, 'year' => $counterYear, 'last_number' => 0]);
            }

            $counter->increment('last_number');

            return $counter->refresh()->last_number;
        });

        return sprintf('%s-%d-%04d', $prefix, $year, $sequence);
    }
}
