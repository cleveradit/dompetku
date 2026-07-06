<?php

declare(strict_types=1);

use Inertia\Testing\AssertableInertia as Assert;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

function searchFilterSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $expense = Category::factory()->forUser($user)->expense()->create(['name' => 'Makan & Minum']);
    $income = Category::factory()->forUser($user)->income()->create(['name' => 'Gaji']);

    return [$user, $wallet, $expense, $income];
}

// AC-17.1: pencarian partial + case-insensitive pada description, dengan pagination 25/halaman.
it('searches transactions by description partially and case-insensitively', function () {
    [$user, $wallet, $expense] = searchFilterSetup();

    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->count(26)->create([
        'description' => 'Bayar KOPI Kenangan',
    ]);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'description' => 'Beli Sepatu',
    ]);

    $this->actingAs($user)
        ->get('/transactions?q=kopi')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/index', false)
            ->has('transactions', 25)
            ->where('pagination.total', 26)
        );
});

// AC-17.2: kombinasi filter (tanggal, kategori, dompet, tipe, nominal) semuanya AND.
it('combines search filters with AND logic', function () {
    [$user, $wallet, $expense, $income] = searchFilterSetup();
    $otherWallet = Wallet::factory()->forUser($user)->create();

    // Cocok semua filter.
    $match = Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'amount' => '50000.00',
        'occurred_on' => '2026-07-05',
    ]);

    // Gagal salah satu filter masing-masing.
    Transaction::factory()->forUser($user)->inWallet($otherWallet)->withCategory($expense)->create([
        'amount' => '50000.00',
        'occurred_on' => '2026-07-05',
    ]);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($income)->create([
        'type' => 'income',
        'amount' => '50000.00',
        'occurred_on' => '2026-07-05',
    ]);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'amount' => '999999.00',
        'occurred_on' => '2026-07-05',
    ]);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'amount' => '50000.00',
        'occurred_on' => '2026-06-01',
    ]);

    $this->actingAs($user)
        ->get('/transactions?'.http_build_query([
            'start' => '2026-07-01',
            'end' => '2026-07-10',
            'categories' => [$expense->id],
            'wallet' => $wallet->id,
            'type' => 'expense',
            'min' => '10000',
            'max' => '100000',
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/index', false)
            ->has('transactions', 1)
            ->where('transactions.0.id', $match->id)
        );
});

// AC-17.3: saat filter aktif, summary.count dan summary.net_total benar.
it('returns a correct summary when filters are active', function () {
    [$user, $wallet, $expense, $income] = searchFilterSetup();

    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'amount' => '30000.00',
        'occurred_on' => '2026-07-01',
    ]);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'amount' => '20000.00',
        'occurred_on' => '2026-07-02',
    ]);
    // Income di luar filter tipe expense, tidak boleh masuk perhitungan.
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($income)->create([
        'type' => 'income',
        'amount' => '999999.00',
        'occurred_on' => '2026-07-01',
    ]);

    $this->actingAs($user)
        ->get('/transactions?type=expense')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/index', false)
            ->where('summary.count', 2)
            ->where('summary.net_total', '-50000.00')
        );
});

// AC-17.4: filter tanpa hasil menghasilkan count 0.
it('returns zero count when filters match nothing', function () {
    [$user, $wallet, $expense] = searchFilterSetup();

    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->create([
        'description' => 'Belanja bulanan',
    ]);

    $this->actingAs($user)
        ->get('/transactions?q=tidakketemu')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('transactions/index', false)
            ->has('transactions', 0)
            ->where('summary.count', 0)
            ->where('summary.net_total', '0.00')
        );
});
