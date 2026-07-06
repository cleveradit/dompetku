<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\Transaction;

class TransferBetweenWallets
{
    public function __construct(private readonly RecordTransaction $recordTransaction) {}

    /**
     * AC-08.1: satu record type=transfer; saldo asal berkurang, tujuan
     * bertambah. Transfer tak berkategori (I-2).
     */
    public function handle(
        int $userId,
        int $sourceWalletId,
        int $destinationWalletId,
        string $amount,
        string $occurredOn,
        ?string $description,
    ): Transaction {
        return $this->recordTransaction->handle(
            userId: $userId,
            type: TransactionType::Transfer,
            walletId: $sourceWalletId,
            destinationWalletId: $destinationWalletId,
            categoryId: null,
            amount: $amount,
            occurredOn: $occurredOn,
            description: $description,
        );
    }
}
