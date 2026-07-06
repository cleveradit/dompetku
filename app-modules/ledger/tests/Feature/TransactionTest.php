<?php

declare(strict_types=1);

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

function ledgerSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('100.00')->create();
    $expense = Category::factory()->forUser($user)->expense()->create(['name' => 'Makan & Minum']);
    $income = Category::factory()->forUser($user)->income()->create(['name' => 'Gaji']);

    return [$user, $wallet, $expense, $income];
}

function expensePayload(Wallet $wallet, Category $category, array $overrides = []): array
{
    return array_merge([
        'type' => 'expense',
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'amount' => '0.10',
        'description' => 'Kopi',
        'occurred_on' => '2026-07-01',
    ], $overrides);
}

// AC-07.1: saldo 100,00 dikurangi 0,10 tiga kali = 99,70 tepat.
it('records expenses and decreases the balance with exact decimals', function () {
    [$user, $wallet, $expense] = ledgerSetup();

    foreach (range(1, 3) as $i) {
        $this->actingAs($user)
            ->post('/transactions', expensePayload($wallet, $expense))
            ->assertRedirect()
            ->assertSessionHasNoErrors();
    }

    expect($wallet->refresh()->current_balance)->toBe('99.70')
        ->and(Transaction::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(3);
});

// AC-07.2
it('records income and increases the balance', function () {
    [$user, $wallet, , $income] = ledgerSetup();

    $this->actingAs($user)
        ->post('/transactions', expensePayload($wallet, $income, ['type' => 'income', 'amount' => '5000000.25']))
        ->assertSessionHasNoErrors();

    expect($wallet->refresh()->current_balance)->toBe('5000100.25');
});

// AC-07.3
it('rejects a category whose type does not match the transaction type', function () {
    [$user, $wallet, $expense, $income] = ledgerSetup();

    $this->actingAs($user)
        ->post('/transactions', expensePayload($wallet, $income)) // expense dengan kategori income
        ->assertSessionHasErrors('category_id');

    $this->actingAs($user)
        ->post('/transactions', expensePayload($wallet, $expense, ['type' => 'income']))
        ->assertSessionHasErrors('category_id');
});

// AC-07.4
it('rejects invalid amounts and keeps the balance unchanged', function (string $amount) {
    [$user, $wallet, $expense] = ledgerSetup();

    $this->actingAs($user)
        ->post('/transactions', expensePayload($wallet, $expense, ['amount' => $amount]))
        ->assertSessionHasErrors('amount');

    expect($wallet->refresh()->current_balance)->toBe('100.00');
})->with(['0', '-5', '10.123', 'abc']);

// AC-07.5
it('rejects a future date', function () {
    [$user, $wallet, $expense] = ledgerSetup();

    $tomorrow = now('Asia/Jakarta')->addDay()->toDateString();

    $this->actingAs($user)
        ->post('/transactions', expensePayload($wallet, $expense, ['occurred_on' => $tomorrow]))
        ->assertSessionHasErrors('occurred_on');
});

// AC-07.6
it('rejects recording into an archived wallet', function () {
    [$user, , $expense] = ledgerSetup();
    $archived = Wallet::factory()->forUser($user)->archived()->create();

    $this->actingAs($user)
        ->post('/transactions', expensePayload($archived, $expense))
        ->assertSessionHasErrors('wallet_id');
});

// AC-07.7: pindah dompet & ubah nominal — saldo lama dan baru terkoreksi.
it('corrects both wallets when a transaction is edited', function () {
    [$user, $wallet, $expense] = ledgerSetup();
    $other = Wallet::factory()->forUser($user)->withBalance('50.00')->create();

    $this->actingAs($user)->post('/transactions', expensePayload($wallet, $expense, ['amount' => '10.00']));
    $transaction = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    expect($wallet->refresh()->current_balance)->toBe('90.00');

    $this->actingAs($user)
        ->patch("/transactions/{$transaction->id}", expensePayload($other, $expense, ['amount' => '20.00']))
        ->assertSessionHasNoErrors();

    expect($wallet->refresh()->current_balance)->toBe('100.00')
        ->and($other->refresh()->current_balance)->toBe('30.00');
});

// AC-07.8
it('soft deletes a transaction and restores the balance', function () {
    [$user, $wallet, $expense] = ledgerSetup();

    $this->actingAs($user)->post('/transactions', expensePayload($wallet, $expense, ['amount' => '25.50']));
    $transaction = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    expect($wallet->refresh()->current_balance)->toBe('74.50');

    $this->actingAs($user)
        ->delete("/transactions/{$transaction->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    expect($wallet->refresh()->current_balance)->toBe('100.00');
});

// AC-07.9
it('accepts an expense that makes the balance negative', function () {
    [$user, $wallet, $expense] = ledgerSetup();

    $this->actingAs($user)
        ->post('/transactions', expensePayload($wallet, $expense, ['amount' => '150.00']))
        ->assertSessionHasNoErrors();

    expect($wallet->refresh()->current_balance)->toBe('-50.00');
});

// 04-NFR.md §1: isolasi data.
it('blocks another user from editing or deleting a transaction', function () {
    [$user, $wallet, $expense] = ledgerSetup();
    $intruder = User::factory()->create();

    $this->actingAs($user)->post('/transactions', expensePayload($wallet, $expense));
    $transaction = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($intruder)
        ->patch("/transactions/{$transaction->id}", expensePayload($wallet, $expense))
        ->assertNotFound();

    $this->actingAs($intruder)
        ->delete("/transactions/{$transaction->id}")
        ->assertNotFound();
});
