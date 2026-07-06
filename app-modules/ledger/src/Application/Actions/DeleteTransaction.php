<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Domain\Support\BalanceEffects;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Application\Actions\AdjustWalletBalance;

class DeleteTransaction
{
    public function __construct(private readonly AdjustWalletBalance $adjustWalletBalance)
    {
    }

    /**
     * AC-07.8 / AC-08.4: soft delete dan saldo kembali seperti sebelum
     * transaksi ada.
     */
    public function handle(Transaction $transaction): void
    {
        DB::transaction(function () use ($transaction): void {
            $reversal = BalanceEffects::invert(BalanceEffects::of(
                $transaction->type,
                $transaction->wallet_id,
                $transaction->destination_wallet_id,
                $transaction->amount,
            ));

            foreach ($reversal as $walletId => $delta) {
                $this->adjustWalletBalance->handle($walletId, $delta);
            }

            $transaction->delete();
        });
    }
}
