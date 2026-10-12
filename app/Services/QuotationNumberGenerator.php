<?php

namespace App\Services;

use App\Models\InvoiceSetting;
use App\Models\NumberCounter;
use Illuminate\Support\Facades\DB;

class QuotationNumberGenerator
{
    public const TYPE_QUOTATION = 'quotation';

    /**
     * Generate the next quotation number, safe for concurrent requests.
     *
     * Format: Q/YYYY/MM/NNN — counter resets every month.
     */
    public function next(?int $year = null, ?int $month = null): string
    {
        $settings = InvoiceSetting::cached();
        $now = now();
        $year ??= (int) $now->format('Y');
        $month ??= (int) $now->format('m');

        $prefix = $settings->quotation_prefix ?: 'Q';
        $period = $year * 100 + $month;

        $sequence = DB::transaction(function () use ($period): int {
            $counter = NumberCounter::query()
                ->where('type', self::TYPE_QUOTATION)
                ->where('year', $period)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                $counter = NumberCounter::create(['type' => self::TYPE_QUOTATION, 'year' => $period, 'last_number' => 0]);
            }

            $counter->increment('last_number');

            return $counter->refresh()->last_number;
        });

        return sprintf('%s/%d/%02d/%03d', $prefix, $year, $month, $sequence);
    }
}
