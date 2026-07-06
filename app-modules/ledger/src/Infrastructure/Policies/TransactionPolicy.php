<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Policies;

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Transaction;

class TransactionPolicy
{
    public function view(User $user, Transaction $transaction): bool
    {
        return $transaction->user_id === $user->id;
    }

    public function update(User $user, Transaction $transaction): bool
    {
        return $transaction->user_id === $user->id;
    }

    public function delete(User $user, Transaction $transaction): bool
    {
        return $transaction->user_id === $user->id;
    }
}
