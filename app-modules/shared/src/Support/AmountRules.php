<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * 04-NFR.md §2 aturan nominal: angka, maksimal 2 desimal, dikirim sebagai
 * string dengan titik desimal, min 0.01 kecuali disebut lain,
 * max 999999999999.99.
 */
final class AmountRules
{
    public const MAX = '999999999999.99';

    /** @return list<string> */
    public static function rules(string $min = '0.01', bool $required = true): array
    {
        return [
            $required ? 'required' : 'nullable',
            'numeric',
            'decimal:0,2',
            'min:'.$min,
            'max:'.self::MAX,
        ];
    }
}
