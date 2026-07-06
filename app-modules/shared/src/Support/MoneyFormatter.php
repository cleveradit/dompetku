<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

use Brick\Money\Money;

final class MoneyFormatter
{
    /**
     * Parse a decimal string (dot as decimal separator) into a Money object.
     */
    public static function of(string $amount, string $currency = 'IDR'): Money
    {
        return Money::of($amount, $currency);
    }

    /**
     * Amount as a plain decimal string with exactly two decimals, e.g. "10500.75".
     * This is the canonical wire format between backend and frontend.
     */
    public static function toDecimalString(Money $money): string
    {
        return $money->getAmount()->toScale(2)->__toString();
    }

    /**
     * Human readable format for server-rendered output (exports, e-mails),
     * e.g. IDR: "Rp1.250.000,50".
     */
    public static function format(Money $money, string $locale = 'id_ID'): string
    {
        return $money->formatTo($locale);
    }
}
