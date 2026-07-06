<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Actions;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Modules\Wallet\Infrastructure\Models\Wallet;

/**
 * SATU-SATUNYA jalur mengubah saldo dompet (01-ARCHITECTURE.md §3), dan hanya
 * boleh dipanggil oleh Action di modul Ledger, di dalam DB::transaction().
 */
class AdjustWalletBalance
{
    /**
     * @param  string  $delta  signed decimal string, e.g. "-10500.75"
     */
    public function handle(int $walletId, string $delta): void
    {
        /** @var Wallet $wallet */
        $wallet = Wallet::withoutGlobalScopes()
            ->whereKey($walletId)
            ->lockForUpdate()
            ->firstOrFail();

        $newBalance = BigDecimal::of($wallet->current_balance)
            ->plus(BigDecimal::of($delta))
            ->toScale(2, RoundingMode::UNNECESSARY);

        $wallet->current_balance = (string) $newBalance;
        $wallet->save();
    }
}
