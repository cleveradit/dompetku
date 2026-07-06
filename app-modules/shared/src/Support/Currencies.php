<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Supported account currencies (04-NFR.md §2). Display-format only — the app
 * never converts between currencies (00-PRD.md out of scope).
 */
final class Currencies
{
    public const DEFAULT = 'IDR';

    /** @var list<string> */
    public const SUPPORTED = ['IDR', 'USD', 'EUR', 'SGD', 'MYR'];

    /** @return array<string, string> code => label for select inputs */
    public static function options(): array
    {
        return [
            'IDR' => 'IDR — Rupiah Indonesia',
            'USD' => 'USD — Dolar Amerika Serikat',
            'EUR' => 'EUR — Euro',
            'SGD' => 'SGD — Dolar Singapura',
            'MYR' => 'MYR — Ringgit Malaysia',
        ];
    }
}
