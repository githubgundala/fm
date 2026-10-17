<?php

use App\Support\MoneyInput;

it('normalises a stored decimal value for display', function (): void {
    expect(MoneyInput::format('300000.00'))->toBe(300000.0)
        ->and(MoneyInput::format(1500))->toBe(1500.0)
        ->and(MoneyInput::format(null))->toBeNull()
        ->and(MoneyInput::format(''))->toBeNull();
});

it('converts a masked IDR value into a clean number', function (): void {
    expect(MoneyInput::toNumber('1.500.000'))->toBe(1500000.0)
        ->and(MoneyInput::toNumber('300.000'))->toBe(300000.0)
        ->and(MoneyInput::toNumber('0'))->toBe(0.0)
        ->and(MoneyInput::toNumber(1500000))->toBe(1500000.0)
        ->and(MoneyInput::toNumber(null))->toBeNull();
});
