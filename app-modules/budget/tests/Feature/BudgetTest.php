<?php

declare(strict_types=1);

use Modules\Budget\Application\Actions\CopyBudgetsFromPreviousMonth;
use Modules\Budget\Infrastructure\Models\Budget;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Reporting\Application\Queries\BudgetProgressQuery;
use Modules\Wallet\Infrastructure\Models\Wallet;

function budgetSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $food = Category::factory()->forUser($user)->expense()->create(['name' => 'Makan & Minum']);

    return [$user, $wallet, $food];
}

// AC-14.1: upsert — satu kategori satu anggaran per bulan.
it('creates and upserts a monthly budget per category', function () {
    [$user, , $food] = budgetSetup();

    $this->actingAs($user)
        ->post('/budgets', [
            'category_id' => $food->id,
            'month' => '2026-07-15', // dinormalisasi ke tanggal 1 (I-8)
            'amount' => '1000000.00',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $this->actingAs($user)
        ->post('/budgets', [
            'category_id' => $food->id,
            'month' => '2026-07-01',
            'amount' => '1500000.00',
        ])
        ->assertSessionHasNoErrors();

    $budgets = Budget::withoutGlobalScopes()->where('user_id', $user->id)->get();
    expect($budgets)->toHaveCount(1)
        ->and($budgets->first()->amount)->toBe('1500000.00')
        ->and($budgets->first()->month->toDateString())->toBe('2026-07-01');
});

// AC-14.2
it('rejects budgeting an income category', function () {
    [$user] = budgetSetup();
    $income = Category::factory()->forUser($user)->income()->create();

    $this->actingAs($user)
        ->post('/budgets', [
            'category_id' => $income->id,
            'month' => '2026-07-01',
            'amount' => '1000000.00',
        ])
        ->assertSessionHasErrors('category_id');
});

// AC-14.3
it('deletes a budget without touching transactions', function () {
    [$user, $wallet, $food] = budgetSetup();
    $budget = Budget::factory()->forUser($user)->forCategory($food)->create();
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($food)->create();

    $this->actingAs($user)
        ->delete("/budgets/{$budget->id}")
        ->assertRedirect();

    expect(Budget::withoutGlobalScopes()->find($budget->id))->toBeNull()
        ->and(Transaction::withoutGlobalScopes()->where('user_id', $user->id)->count())->toBe(1);
});

// AC-14.4
it('copies previous month budgets without overwriting existing ones', function () {
    [$user, , $food] = budgetSetup();
    $transport = Category::factory()->forUser($user)->expense()->create(['name' => 'Transportasi']);

    Budget::factory()->forUser($user)->forCategory($food)->create(['month' => '2026-06-01', 'amount' => '900000.00']);
    Budget::factory()->forUser($user)->forCategory($transport)->create(['month' => '2026-06-01', 'amount' => '400000.00']);
    // Sudah ada anggaran Juli untuk makan — tidak boleh ditimpa.
    Budget::factory()->forUser($user)->forCategory($food)->create(['month' => '2026-07-01', 'amount' => '1200000.00']);

    $copied = app(CopyBudgetsFromPreviousMonth::class)->handle($user->id, '2026-07-01');

    expect($copied)->toBe(1);

    $july = Budget::withoutGlobalScopes()->where('user_id', $user->id)->where('month', '2026-07-01')->get()->keyBy('category_id');
    expect($july)->toHaveCount(2)
        ->and($july->get($food->id)->amount)->toBe('1200000.00')
        ->and($july->get($transport->id)->amount)->toBe('400000.00');
});

// AC-15.1
it('reports progress with spent, remaining, and percent', function () {
    [$user, $wallet, $food] = budgetSetup();
    Budget::factory()->forUser($user)->forCategory($food)->create(['month' => '2026-07-01', 'amount' => '1000000.00']);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($food)->create([
        'amount' => '400000.00',
        'occurred_on' => '2026-07-10',
    ]);

    $progress = app(BudgetProgressQuery::class)->handle($user->id, '2026-07-01');

    expect($progress)->toHaveCount(1)
        ->and($progress[0]['spent'])->toBe('400000.00')
        ->and($progress[0]['remaining'])->toBe('600000.00')
        ->and($progress[0]['percent'])->toBe(40.0)
        ->and($progress[0]['status'])->toBe('ok');
});

// AC-15.2
it('flags warning at 80% and danger at 100%', function () {
    [$user, $wallet, $food] = budgetSetup();
    $transport = Category::factory()->forUser($user)->expense()->create(['name' => 'Transportasi']);

    Budget::factory()->forUser($user)->forCategory($food)->create(['month' => '2026-07-01', 'amount' => '100000.00']);
    Budget::factory()->forUser($user)->forCategory($transport)->create(['month' => '2026-07-01', 'amount' => '100000.00']);

    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($food)->create([
        'amount' => '85000.00',
        'occurred_on' => '2026-07-10',
    ]);
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($transport)->create([
        'amount' => '120000.00',
        'occurred_on' => '2026-07-10',
    ]);

    $progress = collect(app(BudgetProgressQuery::class)->handle($user->id, '2026-07-01'))->keyBy('category_id');

    expect($progress->get($food->id)['status'])->toBe('warning')
        ->and($progress->get($transport->id)['status'])->toBe('danger')
        ->and($progress->get($transport->id)['remaining'])->toBe('-20000.00');
});

// AC-15.3: transfer tidak pernah mengurangi anggaran.
it('never counts transfers against budgets', function () {
    [$user, $wallet, $food] = budgetSetup();
    $other = Wallet::factory()->forUser($user)->create();
    Budget::factory()->forUser($user)->forCategory($food)->create(['month' => '2026-07-01', 'amount' => '100000.00']);

    Transaction::factory()->forUser($user)->inWallet($wallet)->create([
        'category_id' => null,
        'type' => 'transfer',
        'destination_wallet_id' => $other->id,
        'amount' => '50000.00',
        'occurred_on' => '2026-07-10',
    ]);

    $progress = app(BudgetProgressQuery::class)->handle($user->id, '2026-07-01');
    expect($progress[0]['spent'])->toBe('0.00');
});

// AC-15.4: edit/hapus transaksi tercermin di progres.
it('reflects edited and deleted expenses in progress', function () {
    [$user, $wallet, $food] = budgetSetup();
    Budget::factory()->forUser($user)->forCategory($food)->create(['month' => '2026-07-01', 'amount' => '100000.00']);
    $transaction = Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($food)->create([
        'amount' => '60000.00',
        'occurred_on' => '2026-07-10',
    ]);

    $transaction->delete();

    $progress = app(BudgetProgressQuery::class)->handle($user->id, '2026-07-01');
    expect($progress[0]['spent'])->toBe('0.00');
});

// Isolasi data.
it('blocks another user from deleting a budget', function () {
    [$user, , $food] = budgetSetup();
    $intruder = User::factory()->create();
    Wallet::factory()->forUser($intruder)->create();
    $budget = Budget::factory()->forUser($user)->forCategory($food)->create();

    $this->actingAs($intruder)
        ->delete("/budgets/{$budget->id}")
        ->assertNotFound();
});
