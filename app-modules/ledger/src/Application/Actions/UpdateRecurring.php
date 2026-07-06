<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Modules\Ledger\Domain\Enums\RecurringFrequency;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;

class UpdateRecurring
{
    /**
     * AC-16.6: hanya transaksi MENDATANG yang terpengaruh; transaksi yang
     * sudah tercipta tidak diubah.
     */
    public function handle(
        RecurringTransaction $recurring,
        TransactionType $type,
        int $walletId,
        ?int $destinationWalletId,
        ?int $categoryId,
        string $amount,
        ?string $description,
        RecurringFrequency $frequency,
        int $interval,
        string $nextRunOn,
        ?string $endOn,
    ): RecurringTransaction {
        $recurring->fill([
            'wallet_id' => $walletId,
            'destination_wallet_id' => $destinationWalletId,
            'category_id' => $categoryId,
            'type' => $type,
            'amount' => $amount,
            'description' => $description,
            'frequency' => $frequency,
            'interval' => $interval,
            'next_run_on' => $nextRunOn,
            'end_on' => $endOn,
        ]);

        $recurring->save();

        return $recurring;
    }
}
