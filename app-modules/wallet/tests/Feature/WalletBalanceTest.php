<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Wallet\Application\Actions\AdjustWalletBalance;
use Modules\Wallet\Application\Actions\RecalculateWalletBalance;
use Modules\Wallet\Infrastructure\Models\Wallet;

// 04-NFR.md M-3: presisi desimal.
it('adjusts the balance with exact decimal arithmetic', function () {
    $wallet = Wallet::factory()->withBalance('100.00')->create();
    $adjust = app(AdjustWalletBalance::class);

    DB::transaction(function () use ($adjust, $wallet) {
        $adjust->handle($wallet->id, '-0.10');
        $adjust->handle($wallet->id, '-0.10');
        $adjust->handle($wallet->id, '-0.10');
    });

    expect($wallet->refresh()->current_balance)->toBe('99.70');
});

it('adds 0.10 and 0.20 to exactly 0.30', function () {
    $wallet = Wallet::factory()->withBalance('0.00')->create();
    $adjust = app(AdjustWalletBalance::class);

    DB::transaction(function () use ($adjust, $wallet) {
        $adjust->handle($wallet->id, '0.10');
        $adjust->handle($wallet->id, '0.20');
    });

    expect($wallet->refresh()->current_balance)->toBe('0.30');
});

// 02-DATABASE.md §4 & 04-NFR.md R-2.
it('recalculates the cached balance from the transactions table', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('1000.00')->create();
    $other = Wallet::factory()->forUser($user)->withBalance('500.00')->create();
    $category = \Modules\Ledger\Infrastructure\Models\Category::factory()->forUser($user)->create();

    $rows = [
        ['type' => 'income', 'wallet_id' => $wallet->id, 'destination_wallet_id' => null, 'category_id' => $category->id, 'amount' => '250.75'],
        ['type' => 'expense', 'wallet_id' => $wallet->id, 'destination_wallet_id' => null, 'category_id' => $category->id, 'amount' => '100.25'],
        ['type' => 'transfer', 'wallet_id' => $wallet->id, 'destination_wallet_id' => $other->id, 'amount' => '50.00', 'category_id' => null],
        ['type' => 'transfer', 'wallet_id' => $other->id, 'destination_wallet_id' => $wallet->id, 'amount' => '25.50', 'category_id' => null],
    ];

    foreach ($rows as $row) {
        DB::table('transactions')->insert($row + [
            'user_id' => $user->id,
            'occurred_on' => '2026-07-01',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    // Soft-deleted transaction must not count (AC-10.9).
    DB::table('transactions')->insert([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => '999.99',
        'occurred_on' => '2026-07-01',
        'deleted_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    // Corrupt the cache on purpose, then recover.
    $wallet->forceFill(['current_balance' => '0.00'])->save();

    $balance = app(RecalculateWalletBalance::class)->handle($wallet->fresh());

    // 1000.00 + 250.75 - 100.25 - 50.00 + 25.50 = 1126.00
    expect($balance)->toBe('1126.00')
        ->and($wallet->fresh()->current_balance)->toBe('1126.00');
});

it('recalculates via the artisan command', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('75.25')->create();
    $wallet->forceFill(['current_balance' => '123.45'])->save();

    $this->artisan('wallets:recalculate', ['--user' => $user->id])
        ->assertSuccessful();

    expect($wallet->fresh()->current_balance)->toBe('75.25');
});
