<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Policies;

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;

class CategoryPolicy
{
    public function view(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    public function update(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }

    public function delete(User $user, Category $category): bool
    {
        return $category->user_id === $user->id;
    }
}
