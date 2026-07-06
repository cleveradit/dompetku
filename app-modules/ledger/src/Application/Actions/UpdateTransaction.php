<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Domain\Support\BalanceEffects;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Application\Actions\AdjustWalletBalance;

class UpdateTransaction
{
    public function __construct(private readonly AdjustWalletBalance $adjustWalletBalance) {}

    /**
     * AC-07.7: reverse efek lama, apply efek baru — saldo dompet lama dan baru
     * terkoreksi benar dalam satu DB::transaction().
     */
    public function handle(
        Transaction $transaction,
        TransactionType $type,
        int $walletId,
        ?int $destinationWalletId,
        ?int $categoryId,
        string $amount,
        string $occurredOn,
        ?string $description,
    ): Transaction {
        return DB::transaction(function () use (
            $transaction, $type, $walletId, $destinationWalletId,
            $categoryId, $amount, $occurredOn, $description,
        ): Transaction {
            $reversal = BalanceEffects::invert(BalanceEffects::of(
                $transaction->type,
                $transaction->wallet_id,
                $transaction->destination_wallet_id,
                $transaction->amount,
            ));

            $application = BalanceEffects::of($type, $walletId, $destinationWalletId, $amount);

            foreach (BalanceEffects::merge($reversal, $application) as $effectWalletId => $delta) {
                $this->adjustWalletBalance->handle($effectWalletId, $delta);
            }

            $transaction->fill([
                'wallet_id' => $walletId,
                'destination_wallet_id' => $destinationWalletId,
                'category_id' => $categoryId,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'occurred_on' => $occurredOn,
            ]);

            $transaction->save();

            return $transaction;
        });
    }
}
