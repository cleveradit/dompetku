<?php

declare(strict_types=1);

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Application\Queries\TransactionFormOptionsQuery;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Reporting\Application\Queries\DashboardSummaryQuery;
use Modules\Wallet\Infrastructure\Models\Wallet;

// AC-11.1
it('summarizes balances, monthly totals, and the last 10 transactions', function () {
    $user = User::factory()->create();
    $active = Wallet::factory()->forUser($user)->withBalance('1000.50')->create();
    $second = Wallet::factory()->forUser($user)->withBalance('500.25')->create();
    $archived = Wallet::factory()->forUser($user)->archived()->withBalance('9999.00')->create();
    $category = Category::factory()->forUser($user)->expense()->create();

    $today = now('Asia/Jakarta')->toDateString();
    foreach (range(1, 12) as $i) {
        Transaction::factory()->forUser($user)->inWallet($active)->create([
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '10.00',
            'occurred_on' => $today,
        ]);
    }

    $summary = app(DashboardSummaryQuery::class)->handle($user->id);

    // Saldo arsip tidak ikut total (AC-11.1); cache tidak berubah oleh factory
    // (transaksi factory tidak menyentuh saldo — dashboard membaca cache, P-5).
    expect($summary['total_balance'])->toBe('1500.75')
        ->and(collect($summary['wallets'])->pluck('id'))->not->toContain($archived->id)
        ->and($summary['month']['expense'])->toBe('120.00')
        ->and($summary['recent_transactions'])->toHaveCount(10);
});

it('renders the dashboard page', function () {
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->create();

    $this->actingAs($user)->get('/dashboard')->assertOk();
});

// AC-11.3: form transaksi default dompet terakhir dipakai + tanggal hari ini.
it('defaults the transaction form to the last used wallet and today', function () {
    $user = User::factory()->create();
    $first = Wallet::factory()->forUser($user)->create();
    $last = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create();

    Transaction::factory()->forUser($user)->inWallet($first)->create(['category_id' => $category->id]);
    Transaction::factory()->forUser($user)->inWallet($last)->create(['category_id' => $category->id]);

    $this->actingAs($user);
    $options = app(TransactionFormOptionsQuery::class)->handle($user->id);

    expect($options['lastWalletId'])->toBe($last->id)
        ->and($options['today'])->toBe(now('Asia/Jakarta')->toDateString());
});
