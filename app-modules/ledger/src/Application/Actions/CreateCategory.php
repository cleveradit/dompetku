<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Ledger\Infrastructure\Models\Category;

class CreateCategory
{
    public function handle(int $userId, string $name, CategoryType $type, ?string $color, ?string $icon): Category
    {
        return Category::create([
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'color' => $color,
            'icon' => $icon,
            'is_default' => false,
        ]);
    }
}
