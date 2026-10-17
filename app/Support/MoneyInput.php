<?php

namespace App\Support;

use Filament\Support\RawJs;

class MoneyInput
{
    /**
     * Alpine.js dynamic mask for Indonesian Rupiah.
     *
     * Thousands separator ".", no decimals.
     */
    public static function mask(): RawJs
    {
        return RawJs::make("\$money(\$input, ',', '.', 0)");
    }

    /**
     * Normalise a hydrated value (e.g. the decimal string "300000.00" coming
     * from the database) into a plain number so the IDR mask renders it
     * correctly instead of treating the decimal point as a thousands separator.
     */
    public static function format(mixed $state): ?float
    {
        if (blank($state)) {
            return null;
        }

        return (float) $state;
    }

    /**
     * Convert a masked IDR value (e.g. "1.500.000") into a clean float.
     */
    public static function toNumber(mixed $state): ?float
    {
        if (blank($state)) {
            return null;
        }

        return (float) str_replace(['.', ',', ' '], '', (string) $state);
    }
}
