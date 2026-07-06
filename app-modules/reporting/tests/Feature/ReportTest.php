<?php

declare(strict_types=1);

use Carbon\CarbonImmutable;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Reporting\Application\Queries\SpendingByCategoryQuery;
use Modules\Reporting\Application\Queries\SpendingByPeriodQuery;
use Modules\Shared\ValueObjects\DatePeriod;
use Modules\Wallet\Infrastructure\Models\Wallet;

function reportSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('1000.00')->create();
    $food = Category::factory()->forUser($user)->expense()->create(['name' => 'Makan & Minum']);
    $transport = Category::factory()->forUser($user)->expense()->create(['name' => 'Transportasi']);
    $salary = Category::factory()->forUser($user)->income()->create(['name' => 'Gaji']);

    return [$user, $wallet, $food, $transport, $salary];
}

function seedTransaction(User $user, Wallet $wallet, ?Category $category, string $type, string $amount, string $date, array $extra = []): Transaction
{
    return Transaction::factory()->forUser($user)->inWallet($wallet)->create(array_merge([
        'category_id' => $category?->id,
        'type' => $type,
        'amount' => $amount,
        'occurred_on' => $date,
    ], $extra));
}

// AC-10.1
it('returns monthly totals and an expense breakdown sorted by amount', function () {
    [$user, $wallet, $food, $transport, $salary] = reportSetup();

    seedTransaction($user, $wallet, $food, 'expense', '150000.00', '2026-07-05');
    seedTransaction($user, $wallet, $food, 'expense', '50000.00', '2026-07-10');
    seedTransaction($user, $wallet, $transport, 'expense', '300000.00', '2026-07-12');
    seedTransaction($user, $wallet, $salary, 'income', '5000000.00', '2026-07-01');

    $period = DatePeriod::monthly(CarbonImmutable::parse('2026-07-15'));

    $totals = app(SpendingByPeriodQuery::class)->totals($user->id, $period);
    expect($totals['income'])->toBe('5000000.00')
        ->and($totals['expense'])->toBe('500000.00')
        ->and($totals['net'])->toBe('4500000.00');

    $breakdown = app(SpendingByCategoryQuery::class)->handle($user->id, $period);
    expect($breakdown)->toHaveCount(2)
        ->and($breakdown[0]['name'])->toBe('Transportasi')
        ->and($breakdown[0]['amount'])->toBe('300000.00')
        ->and($breakdown[0]['percent'])->toBe(60.0)
        ->and($breakdown[1]['name'])->toBe('Makan & Minum')
        ->and($breakdown[1]['percent'])->toBe(40.0);
});

// AC-08.3: transfer tidak dihitung expense/income.
it('excludes transfers from income and expense totals', function () {
    [$user, $wallet] = reportSetup();
    $other = Wallet::factory()->forUser($user)->create();

    seedTransaction($user, $wallet, null, 'transfer', '250000.00', '2026-07-05', [
        'destination_wallet_id' => $other->id,
    ]);

    $totals = app(SpendingByPeriodQuery::class)->totals($user->id, DatePeriod::monthly(CarbonImmutable::parse('2026-07-15')));

    expect($totals['income'])->toBe('0.00')
        ->and($totals['expense'])->toBe('0.00');
});

// AC-10.2: minggu mulai Senin.
it('builds weekly periods starting on Monday', function () {
    $period = DatePeriod::weekly(CarbonImmutable::parse('2026-07-08')); // Rabu

    expect($period->start->toDateString())->toBe('2026-07-06') // Senin
        ->and($period->end->toDateString())->toBe('2026-07-12'); // Minggu
});

// AC-10.3: rentang custom inklusif.
it('includes both boundary dates of a custom range', function () {
    [$user, $wallet, $food] = reportSetup();

    seedTransaction($user, $wallet, $food, 'expense', '10.00', '2026-07-01');
    seedTransaction($user, $wallet, $food, 'expense', '20.00', '2026-07-31');
    seedTransaction($user, $wallet, $food, 'expense', '99.00', '2026-08-01');

    $period = DatePeriod::custom(CarbonImmutable::parse('2026-07-01'), CarbonImmutable::parse('2026-07-31'));
    $totals = app(SpendingByPeriodQuery::class)->totals($user->id, $period);

    expect($totals['expense'])->toBe('30.00');
});

// AC-10.4
it('rejects an invalid custom range via HTTP', function () {
    [$user] = reportSetup();

    $this->actingAs($user)
        ->get('/reports?interval=custom&start=2026-07-10&end=2026-07-01')
        ->assertSessionHasErrors('end');

    $this->actingAs($user)
        ->get('/reports?interval=custom&start=2025-01-01&end=2026-06-30')
        ->assertSessionHasErrors('end');
});

// AC-10.5: periode kosong tetap 200.
it('renders an empty period without errors', function () {
    [$user] = reportSetup();

    $this->actingAs($user)
        ->get('/reports?interval=monthly&anchor=2020-01-15')
        ->assertOk();
});

// AC-10.6: tren bulanan per hari, tahunan per bulan.
it('produces a daily trend for months and a monthly trend for years', function () {
    [$user, $wallet, $food] = reportSetup();
    seedTransaction($user, $wallet, $food, 'expense', '10.00', '2026-07-05');

    $monthly = app(SpendingByPeriodQuery::class)->trend($user->id, DatePeriod::monthly(CarbonImmutable::parse('2026-07-15')));
    expect($monthly)->toHaveCount(31)
        ->and(collect($monthly)->firstWhere('key', '2026-07-05')['expense'])->toBe('10.00');

    $yearly = app(SpendingByPeriodQuery::class)->trend($user->id, DatePeriod::yearly(CarbonImmutable::parse('2026-07-15')));
    expect($yearly)->toHaveCount(12)
        ->and(collect($yearly)->firstWhere('key', '2026-07')['expense'])->toBe('10.00');
});

// AC-10.8
it('only counts the selected wallet when filtered', function () {
    [$user, $wallet, $food] = reportSetup();
    $other = Wallet::factory()->forUser($user)->create();

    seedTransaction($user, $wallet, $food, 'expense', '100.00', '2026-07-05');
    seedTransaction($user, $other, $food, 'expense', '999.00', '2026-07-06');

    $period = DatePeriod::monthly(CarbonImmutable::parse('2026-07-15'));
    $totals = app(SpendingByPeriodQuery::class)->totals($user->id, $period, $wallet->id);

    expect($totals['expense'])->toBe('100.00');
});

// AC-10.9
it('excludes soft-deleted transactions from every report', function () {
    [$user, $wallet, $food] = reportSetup();

    $transaction = seedTransaction($user, $wallet, $food, 'expense', '100.00', '2026-07-05');
    $transaction->delete();

    $period = DatePeriod::monthly(CarbonImmutable::parse('2026-07-15'));
    expect(app(SpendingByPeriodQuery::class)->totals($user->id, $period)['expense'])->toBe('0.00')
        ->and(app(SpendingByCategoryQuery::class)->handle($user->id, $period))->toBe([]);
});

// AC-10.10
it('navigates to previous and next periods with the same interval', function () {
    $period = DatePeriod::monthly(CarbonImmutable::parse('2026-07-15'));

    expect($period->previous()->start->toDateString())->toBe('2026-06-01')
        ->and($period->next()->start->toDateString())->toBe('2026-08-01');
});

// Isolasi data: laporan user lain tidak bocor.
it('never mixes another user\'s data into reports', function () {
    [$user, $wallet, $food] = reportSetup();
    [$stranger, $strangerWallet, $strangerFood] = reportSetup();

    seedTransaction($stranger, $strangerWallet, $strangerFood, 'expense', '777.00', '2026-07-05');

    $period = DatePeriod::monthly(CarbonImmutable::parse('2026-07-15'));
    expect(app(SpendingByPeriodQuery::class)->totals($user->id, $period)['expense'])->toBe('0.00');
});
