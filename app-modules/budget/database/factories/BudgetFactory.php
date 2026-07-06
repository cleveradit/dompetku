<?php

declare(strict_types=1);

namespace Modules\Budget\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Budget\Infrastructure\Models\Budget;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;

/**
 * @extends Factory<Budget>
 */
class BudgetFactory extends Factory
{
    protected $model = Budget::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'category_id' => Category::factory(),
            'month' => '2026-07-01',
            'amount' => '1000000.00',
        ];
    }

    /** Pengganti ->for(): relasi Eloquent lintas modul dilarang. */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function forCategory(Category $category): static
    {
        return $this->state(fn () => ['category_id' => $category->id]);
    }
}
