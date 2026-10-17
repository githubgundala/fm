<?php

namespace App\Support;

use Filament\Schemas\Components\StateCasts\Contracts\StateCast;

/**
 * Casts the state of a money input between the value stored in the database
 * (a decimal string such as "300000.00") and the value bound to the
 * IDR-masked text field (such as "300.000").
 */
class MoneyStateCast implements StateCast
{
    /**
     * @param  bool  $hasGrouping  Whether the input uses "." as a thousands
     *                             separator (true for IDR amounts, false for
     *                             values like percentages that share the field).
     */
    public function __construct(
        protected bool $hasGrouping = true,
    ) {}

    /**
     * State going out to the input (hydration).
     */
    public function set(mixed $state): ?float
    {
        return MoneyInput::format($state);
    }

    /**
     * State coming back from the input (dehydration / saving).
     */
    public function get(mixed $state): ?float
    {
        return $this->hasGrouping
            ? MoneyInput::toNumber($state)
            : MoneyInput::format($state);
    }
}
