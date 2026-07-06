<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Support;

use Brick\Math\BigDecimal;
use Modules\Ledger\Domain\Enums\TransactionType;

/**
 * Efek sebuah transaksi terhadap saldo dompet (02-DATABASE.md §4), sebagai
 * peta wallet_id => delta (signed decimal string). Arah dana ditentukan type,
 * bukan tanda nominal (I-4).
 */
final class BalanceEffects
{
    /** @return array<int, string> */
    public static function of(TransactionType $type, int $walletId, ?int $destinationWalletId, string $amount): array
    {
        $amount = (string) BigDecimal::of($amount)->toScale(2);
        $negated = (string) BigDecimal::of($amount)->negated()->toScale(2);

        return match ($type) {
            TransactionType::Income => [$walletId => $amount],
            TransactionType::Expense => [$walletId => $negated],
            TransactionType::Transfer => self::merge([
                $walletId => $negated,
                (int) $destinationWalletId => $amount,
            ]),
        };
    }

    /** @param  array<int, string>  $effects
     * @return array<int, string> */
    public static function invert(array $effects): array
    {
        $inverted = [];
        foreach ($effects as $walletId => $delta) {
            $inverted[$walletId] = (string) BigDecimal::of($delta)->negated()->toScale(2);
        }

        return $inverted;
    }

    /**
     * Merge dua peta efek dengan penjumlahan presisi, kunci diurutkan naik
     * (lock ordering konsisten untuk mencegah deadlock).
     *
     * @param  array<int, string>  ...$maps
     * @return array<int, string>
     */
    public static function merge(array ...$maps): array
    {
        $result = [];

        foreach ($maps as $map) {
            foreach ($map as $walletId => $delta) {
                $current = $result[$walletId] ?? '0.00';
                $result[$walletId] = (string) BigDecimal::of($current)->plus(BigDecimal::of($delta))->toScale(2);
            }
        }

        ksort($result);

        return $result;
    }
}
