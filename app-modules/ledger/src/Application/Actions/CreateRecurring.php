<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Modules\Ledger\Domain\Enums\RecurringFrequency;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;

class CreateRecurring
{
    /** AC-16.1: tersimpan dengan next_run_on = tanggal mulai. */
    public function handle(
        int $userId,
        TransactionType $type,
        int $walletId,
        ?int $destinationWalletId,
        ?int $categoryId,
        string $amount,
        ?string $description,
        RecurringFrequency $frequency,
        int $interval,
        string $startOn,
        ?string $endOn,
    ): RecurringTransaction {
        return RecurringTransaction::create([
            'user_id' => $userId,
            'wallet_id' => $walletId,
            'destination_wallet_id' => $destinationWalletId,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'frequency' => $frequency,
            'interval' => $interval,
            'next_run_on' => $startOn,
            'end_on' => $endOn,
            'is_active' => true,
        ]);
    }
}
