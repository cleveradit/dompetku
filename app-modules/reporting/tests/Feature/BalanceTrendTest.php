<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Reporting\Application\Queries\BalanceTrendQuery;
use Modules\Shared\ValueObjects\DatePeriod;
use Modules\Wallet\Infrastructure\Models\Wallet;

// AC-21.1: saldo kumulatif harian, termasuk carry-over hari tanpa transaksi
// dan mutasi sebelum periode diperhitungkan sebagai saldo awal. Transaction
// factory tidak meng-update cache saldo, jadi ini membuktikan perhitungan
// murni dari initial_balance + mutasi, bukan dari current_balance.
it('computes the cumulative daily balance from initial balance and mutations, ignoring the cache', function () {
    $user = User::factory()->create();
    $walletA = Wallet::factory()->forUser($user)->withBalance('1000.00')->create();
    $walletB = Wallet::factory()->forUser($user)->withBalance('500.00')->create();
    $food = Category::factory()->forUser($user)->expense()->create();
    $salary = Category::factory()->forUser($user)->income()->create();

    // Mutasi sebelum periode (Juli): harus masuk sebagai bagian saldo awal.
    Transaction::factory()->forUser($user)->inWallet($walletA)->create([
        'category_id' => $food->id,
        'type' => 'expense',
        'amount' => '200.00',
        'occurred_on' => '2026-06-20',
    ]);

    // Mutasi dalam periode.
    Transaction::factory()->forUser($user)->inWallet($walletA)->create([
        'category_id' => $salary->id,
        'type' => 'income',
        'amount' => '300.00',
        'occurred_on' => '2026-07-02',
    ]);
    Transaction::factory()->forUser($user)->inWallet($walletB)->create([
        'category_id' => $food->id,
        'type' => 'expense',
        'amount' => '150.00',
        'occurred_on' => '2026-07-05',
    ]);

    $period = DatePeriod::custom(CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-06'));
    $series = app(BalanceTrendQuery::class)->handle($user->id, $period);

    // Saldo awal gabungan = 1000 + 500 - 200 (mutasi sebelum periode) = 1300.00
    expect($series)->toHaveCount(6)
        ->and($series[0])->toBe(['date' => '2026-07-01', 'balance' => '1300.00'])
        // Hari tanpa transaksi carry-over dari hari sebelumnya.
        ->and($series[1])->toBe(['date' => '2026-07-02', 'balance' => '1600.00'])
        ->and($series[2])->toBe(['date' => '2026-07-03', 'balance' => '1600.00'])
        ->and($series[3])->toBe(['date' => '2026-07-04', 'balance' => '1600.00'])
        ->and($series[4])->toBe(['date' => '2026-07-05', 'balance' => '1450.00'])
        ->and($series[5])->toBe(['date' => '2026-07-06', 'balance' => '1450.00']);
});

// Transfer antar dompet sendiri net 0 terhadap saldo gabungan.
it('does not change the total balance trend for transfers between own wallets', function () {
    $user = User::factory()->create();
    $walletA = Wallet::factory()->forUser($user)->withBalance('1000.00')->create();
    $walletB = Wallet::factory()->forUser($user)->withBalance('0.00')->create();

    Transaction::factory()->forUser($user)->inWallet($walletA)->create([
        'category_id' => null,
        'type' => 'transfer',
        'amount' => '400.00',
        'destination_wallet_id' => $walletB->id,
        'occurred_on' => '2026-07-03',
    ]);

    $period = DatePeriod::custom(CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-05'));
    $series = app(BalanceTrendQuery::class)->handle($user->id, $period);

    foreach ($series as $point) {
        expect($point['balance'])->toBe('1000.00');
    }
});

// Transaksi soft-deleted tidak dihitung.
it('excludes soft-deleted transactions from the balance trend', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('1000.00')->create();
    $food = Category::factory()->forUser($user)->expense()->create();

    $transaction = Transaction::factory()->forUser($user)->inWallet($wallet)->create([
        'category_id' => $food->id,
        'type' => 'expense',
        'amount' => '300.00',
        'occurred_on' => '2026-07-02',
    ]);
    $transaction->delete();

    $period = DatePeriod::custom(CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-03'));
    $series = app(BalanceTrendQuery::class)->handle($user->id, $period);

    foreach ($series as $point) {
        expect($point['balance'])->toBe('1000.00');
    }
});

// Halaman laporan tetap 200 dan membawa prop balanceTrend.
it('exposes the balance trend prop on the reports page', function () {
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->withBalance('1000.00')->create();

    $this->actingAs($user)
        ->get('/reports?interval=monthly&anchor=2026-07-15')
        ->assertInertia(fn ($page) => $page->component('reports/index', false)->has('balanceTrend'));
});
