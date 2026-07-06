<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Ledger\Infrastructure\Models\Category;

/**
 * @extends Factory<Category>
 */
class CategoryFactory extends Factory
{
    protected $model = Category::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->words(2, true),
            'type' => CategoryType::Expense,
            'color' => '#B94A48',
            'icon' => 'utensils',
            'is_default' => false,
        ];
    }

    /** Pengganti ->for(): relasi Eloquent lintas modul dilarang (01-ARCHITECTURE.md §2). */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function income(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Income]);
    }

    public function expense(): static
    {
        return $this->state(fn () => ['type' => CategoryType::Expense]);
    }
}
