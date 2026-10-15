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
     * Preview the next invoice number without consuming it.
     *
     * Used to pre-fill the create form; the counter is only
     * incremented when the invoice is actually saved.
     */
    public function peek(?int $year = null, ?string $type = null): string
    {
        $type ??= self::TYPE_INVOICE;

        return $this->peekFor($type, $year);
    }

    public function peekFor(string $type, ?int $year = null): string
    {
        $settings = InvoiceSetting::cached();
        $year ??= (int) now()->format('Y');

        $counterYear = $settings->yearly_reset ? $year : 0;

        $lastNumber = NumberCounter::query()
            ->where('type', $type)
            ->where('year', $counterYear)
            ->value('last_number') ?? 0;

        $prefix = $type === self::TYPE_PAYMENT ? $settings->payment_prefix : $settings->invoice_prefix;

        return sprintf('%s-%d-%04d', $prefix, $year, $lastNumber + 1);
    }

    /**
     * Advance the counter when a manually entered number exceeds it,
     * so future auto-generated numbers cannot collide with it.
     */
    public function syncCounter(string $number, ?string $type = null): void
    {
        $settings = InvoiceSetting::cached();
        $year = (int) now()->format('Y');

        if (! preg_match('/^(.+)-(\d{4})-(\d+)$/', $number, $matches)) {
            return;
        }

        $type ??= $matches[1] === $settings->payment_prefix ? self::TYPE_PAYMENT : self::TYPE_INVOICE;

        $expectedPrefix = $type === self::TYPE_PAYMENT ? $settings->payment_prefix : $settings->invoice_prefix;

        if ($matches[1] !== $expectedPrefix || (int) $matches[2] !== $year) {
            return;
        }

        $sequence = (int) $matches[3];
        $counterYear = $settings->yearly_reset ? $year : 0;

        DB::transaction(function () use ($type, $counterYear, $sequence): void {
            $counter = NumberCounter::query()
                ->where('type', $type)
                ->where('year', $counterYear)
                ->lockForUpdate()
                ->first();

            if (! $counter) {
                NumberCounter::create(['type' => $type, 'year' => $counterYear, 'last_number' => $sequence]);

                return;
            }

            if ($sequence > $counter->last_number) {
                $counter->update(['last_number' => $sequence]);
            }
        });
    }

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
