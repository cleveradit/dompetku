<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Actions;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Modules\Wallet\Infrastructure\Models\Wallet;

/**
 * 02-DATABASE.md §4: sumber kebenaran saldo adalah tabel transactions;
 * current_balance hanyalah cache. Action ini menghitung ulang dari rumus dan
 * dipakai command wallets:recalculate untuk recovery. Idempotent.
 */
class RecalculateWalletBalance
{
    public function handle(Wallet $wallet): string
    {
        $sums = DB::table('transactions')
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(CASE WHEN type = 'income' AND wallet_id = ? THEN amount ELSE 0 END), 0) AS income_in,
                COALESCE(SUM(CASE WHEN type = 'expense' AND wallet_id = ? THEN amount ELSE 0 END), 0) AS expense_out,
                COALESCE(SUM(CASE WHEN type = 'transfer' AND wallet_id = ? THEN amount ELSE 0 END), 0) AS transfer_out,
                COALESCE(SUM(CASE WHEN type = 'transfer' AND destination_wallet_id = ? THEN amount ELSE 0 END), 0) AS transfer_in
            SQL, [$wallet->id, $wallet->id, $wallet->id, $wallet->id])
            ->whereNull('deleted_at')
            ->where(fn ($query) => $query
                ->where('wallet_id', $wallet->id)
                ->orWhere('destination_wallet_id', $wallet->id))
            ->first();

        $balance = BigDecimal::of($wallet->initial_balance)
            ->plus(BigDecimal::of((string) $sums->income_in))
            ->minus(BigDecimal::of((string) $sums->expense_out))
            ->minus(BigDecimal::of((string) $sums->transfer_out))
            ->plus(BigDecimal::of((string) $sums->transfer_in))
            ->toScale(2, RoundingMode::UNNECESSARY);

        $wallet->current_balance = (string) $balance;
        $wallet->save();

        return (string) $balance;
    }
}
