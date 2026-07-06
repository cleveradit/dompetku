<?php

declare(strict_types=1);

namespace Modules\Wallet\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Wallet\Domain\Enums\WalletType;
use Modules\Wallet\Infrastructure\Models\Wallet;

/**
 * @extends Factory<Wallet>
 */
class WalletFactory extends Factory
{
    protected $model = Wallet::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'name' => fake()->unique()->word().' '.fake()->numberBetween(1, 99),
            'type' => WalletType::Bank,
            'initial_balance' => '0.00',
            'current_balance' => '0.00',
            'color' => '#3E5BAA',
            'icon' => 'landmark',
            'is_archived' => false,
        ];
    }

    /** Pengganti ->for(): relasi Eloquent lintas modul dilarang (01-ARCHITECTURE.md §2). */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function archived(): static
    {
        return $this->state(fn () => ['is_archived' => true]);
    }

    public function withBalance(string $balance): static
    {
        return $this->state(fn () => [
            'initial_balance' => $balance,
            'current_balance' => $balance,
        ]);
    }
}
