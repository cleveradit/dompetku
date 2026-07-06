<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Domain\Events\TransactionRecorded;
use Modules\Ledger\Domain\Support\BalanceEffects;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Application\Actions\AdjustWalletBalance;

class RecordTransaction
{
    public function __construct(private readonly AdjustWalletBalance $adjustWalletBalance)
    {
    }

    /**
     * Catat income/expense/transfer. Semua invariant I-1..I-5 sudah divalidasi
     * FormRequest; mutasi transaksi + saldo dibungkus satu DB::transaction()
     * dengan lockForUpdate pada baris wallet (02-DATABASE.md §4).
     */
    public function handle(
        int $userId,
        TransactionType $type,
        int $walletId,
        ?int $destinationWalletId,
        ?int $categoryId,
        string $amount,
        string $occurredOn,
        ?string $description,
        ?int $recurringTransactionId = null,
    ): Transaction {
        $transaction = DB::transaction(function () use (
            $userId, $type, $walletId, $destinationWalletId, $categoryId,
            $amount, $occurredOn, $description, $recurringTransactionId,
        ): Transaction {
            $effects = BalanceEffects::of($type, $walletId, $destinationWalletId, $amount);

            foreach ($effects as $effectWalletId => $delta) {
                $this->adjustWalletBalance->handle($effectWalletId, $delta);
            }

            return Transaction::create([
                'user_id' => $userId,
                'wallet_id' => $walletId,
                'destination_wallet_id' => $destinationWalletId,
                'category_id' => $categoryId,
                'recurring_transaction_id' => $recurringTransactionId,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'occurred_on' => $occurredOn,
            ]);
        });

        TransactionRecorded::dispatch($transaction->id, $userId);

        return $transaction;
    }
}
