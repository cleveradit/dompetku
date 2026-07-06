<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Application\Actions\RunDueRecurringTransactions;
use Modules\Ledger\Application\Actions\ToggleRecurring;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

function recurringSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('1000000.00')->create();
    $category = Category::factory()->forUser($user)->expense()->create();

    return [$user, $wallet, $category];
}

// AC-16.1
it('creates a recurring schedule with next_run_on equal to the start date', function () {
    [$user, $wallet, $category] = recurringSetup();

    $this->actingAs($user)
        ->post('/recurring', [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => '50000.00',
            'description' => 'Langganan',
            'frequency' => 'monthly',
            'interval' => 1,
            'next_run_on' => '2026-08-01',
            'end_on' => null,
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $recurring = RecurringTransaction::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    expect($recurring->next_run_on->toDateString())->toBe('2026-08-01')
        ->and($recurring->is_active)->toBeTrue();
});

// AC-16.2
it('creates the real transaction when due and advances the schedule', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'amount' => '50000.00',
        'next_run_on' => '2026-07-06',
    ]);

    $created = app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-07-06'));

    expect($created)->toBe(1);

    $transaction = Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $recurring->id)->firstOrFail();
    expect($transaction->occurred_on->toDateString())->toBe('2026-07-06')
        ->and($transaction->amount)->toBe('50000.00')
        ->and($wallet->refresh()->current_balance)->toBe('950000.00');

    $recurring->refresh();
    expect($recurring->next_run_on->toDateString())->toBe('2026-08-06')
        ->and($recurring->last_run_on->toDateString())->toBe('2026-07-06');
});

// AC-16.3: catch-up + idempotent.
it('catches up missed days without duplicating on a second run', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'frequency' => 'daily',
        'amount' => '10000.00',
        'next_run_on' => '2026-07-04',
    ]);

    // Server "mati" 3 hari; berjalan lagi tanggal 6.
    $today = CarbonImmutable::parse('2026-07-06');
    $created = app(RunDueRecurringTransactions::class)->handle($today);
    expect($created)->toBe(3);

    $dates = Transaction::withoutGlobalScopes()
        ->where('recurring_transaction_id', $recurring->id)
        ->orderBy('occurred_on')
        ->pluck('occurred_on')
        ->map(fn ($date) => $date->toDateString());
    expect($dates->all())->toBe(['2026-07-04', '2026-07-05', '2026-07-06']);

    // Idempotent (I-11): jalankan lagi — tidak ada duplikat.
    $again = app(RunDueRecurringTransactions::class)->handle($today);
    expect($again)->toBe(0)
        ->and(Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $recurring->id)->count())->toBe(3)
        ->and($wallet->refresh()->current_balance)->toBe('970000.00');
});

// AC-16.4
it('skips paused schedules and resumes without back-filling the pause', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'frequency' => 'daily',
        'next_run_on' => '2026-07-01',
        'is_active' => false,
    ]);

    app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-07-06'));
    expect(Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $recurring->id)->count())->toBe(0);

    // Aktifkan lagi "hari ini" — next_run_on melompat ke depan tanpa rapel.
    $this->travelTo('2026-07-06 08:00:00', function () use ($recurring) {
        app(ToggleRecurring::class)->handle($recurring, true);
    });

    $recurring->refresh();
    expect($recurring->is_active)->toBeTrue()
        ->and($recurring->next_run_on->toDateString())->toBe('2026-07-06');
});

// AC-16.5
it('deactivates automatically after end_on has passed', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'frequency' => 'daily',
        'next_run_on' => '2026-07-04',
        'end_on' => '2026-07-05',
    ]);

    $created = app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-07-10'));

    expect($created)->toBe(2); // 4 & 5 Juli saja
    expect($recurring->refresh()->is_active)->toBeFalse();
});

// AC-16.6
it('only affects future transactions when the schedule is edited', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'amount' => '50000.00',
        'frequency' => 'monthly',
        'next_run_on' => '2026-07-01',
    ]);

    app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-07-01'));

    $this->actingAs($user)
        ->patch("/recurring/{$recurring->id}", [
            'type' => 'expense',
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'amount' => '75000.00',
            'frequency' => 'monthly',
            'interval' => 1,
            'next_run_on' => '2026-08-01',
            'end_on' => null,
        ])
        ->assertSessionHasNoErrors();

    // Transaksi lama tidak berubah…
    $july = Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $recurring->id)->firstOrFail();
    expect($july->amount)->toBe('50000.00');

    // …transaksi mendatang memakai nominal baru.
    app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-08-01'));
    $august = Transaction::withoutGlobalScopes()
        ->where('recurring_transaction_id', $recurring->id)
        ->whereDate('occurred_on', '2026-08-01')
        ->firstOrFail();
    expect($august->amount)->toBe('75000.00');
});

// AC-16.7: transaksi hasil recurring bisa diedit/dihapus seperti biasa.
it('lets generated transactions be edited and deleted like normal ones', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'amount' => '50000.00',
        'next_run_on' => '2026-07-01',
    ]);

    app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-07-01'));
    $transaction = Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $recurring->id)->firstOrFail();

    expect($transaction->recurring_transaction_id)->toBe($recurring->id);

    $this->actingAs($user)
        ->delete("/transactions/{$transaction->id}")
        ->assertRedirect();

    $this->assertSoftDeleted('transactions', ['id' => $transaction->id]);
    expect($wallet->refresh()->current_balance)->toBe('1000000.00');
});

// I-5: dompet terarsip tidak menerima transaksi recurring baru.
it('skips schedules whose wallet is archived', function () {
    [$user, $wallet, $category] = recurringSetup();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'next_run_on' => '2026-07-01',
    ]);
    $wallet->forceFill(['is_archived' => true])->save();

    $created = app(RunDueRecurringTransactions::class)->handle(CarbonImmutable::parse('2026-07-06'));

    expect($created)->toBe(0)
        ->and(Transaction::withoutGlobalScopes()->where('recurring_transaction_id', $recurring->id)->count())->toBe(0);
});
