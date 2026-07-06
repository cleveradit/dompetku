<?php

declare(strict_types=1);

namespace Modules\Ledger\Database\Factories;

use Illuminate\Database\Eloquent\Factories\Factory;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Domain\Enums\RecurringFrequency;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

/**
 * @extends Factory<RecurringTransaction>
 */
class RecurringTransactionFactory extends Factory
{
    protected $model = RecurringTransaction::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'user_id' => User::factory(),
            'wallet_id' => Wallet::factory(),
            'category_id' => Category::factory(),
            'type' => TransactionType::Expense,
            'amount' => '50000.00',
            'description' => 'Langganan bulanan',
            'frequency' => RecurringFrequency::Monthly,
            'interval' => 1,
            'next_run_on' => '2026-07-01',
            'end_on' => null,
            'last_run_on' => null,
            'is_active' => true,
        ];
    }

    /** Pengganti ->for(): relasi Eloquent lintas modul dilarang. */
    public function forUser(User $user): static
    {
        return $this->state(fn () => ['user_id' => $user->id]);
    }

    public function inWallet(Wallet $wallet): static
    {
        return $this->state(fn () => ['wallet_id' => $wallet->id]);
    }

    public function withCategory(Category $category): static
    {
        return $this->state(fn () => [
            'category_id' => $category->id,
            'type' => $category->type->value,
        ]);
    }
}
