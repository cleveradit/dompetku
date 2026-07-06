<?php

declare(strict_types=1);

namespace Modules\Budget\Infrastructure\Policies;

use Modules\Budget\Infrastructure\Models\Budget;
use Modules\Identity\Infrastructure\Models\User;

class BudgetPolicy
{
    public function view(User $user, Budget $budget): bool
    {
        return $budget->user_id === $user->id;
    }

    public function update(User $user, Budget $budget): bool
    {
        return $budget->user_id === $user->id;
    }

    public function delete(User $user, Budget $budget): bool
    {
        return $budget->user_id === $user->id;
    }
}
