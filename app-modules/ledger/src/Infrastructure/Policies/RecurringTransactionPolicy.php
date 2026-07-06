<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Policies;

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;

class RecurringTransactionPolicy
{
    public function view(User $user, RecurringTransaction $recurring): bool
    {
        return $recurring->user_id === $user->id;
    }

    public function update(User $user, RecurringTransaction $recurring): bool
    {
        return $recurring->user_id === $user->id;
    }

    public function delete(User $user, RecurringTransaction $recurring): bool
    {
        return $recurring->user_id === $user->id;
    }
}
