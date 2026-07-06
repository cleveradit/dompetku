<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Policies;

use Modules\Identity\Infrastructure\Models\User;
use Modules\Wallet\Infrastructure\Models\Wallet;

class WalletPolicy
{
    public function view(User $user, Wallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }

    public function update(User $user, Wallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }

    public function delete(User $user, Wallet $wallet): bool
    {
        return $wallet->user_id === $user->id;
    }
}
