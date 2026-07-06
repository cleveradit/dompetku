<?php

declare(strict_types=1);

use Illuminate\Support\Facades\DB;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Wallet\Infrastructure\Models\Wallet;

function walletPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'BCA',
        'type' => 'bank',
        'initial_balance' => '1500000.50',
        'color' => '#3E5BAA',
        'icon' => 'landmark',
    ], $overrides);
}

// AC-05.1
it('creates a wallet with a decimal initial balance as its current balance', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/wallets', walletPayload())
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $wallet = Wallet::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    expect($wallet->name)->toBe('BCA')
        ->and($wallet->initial_balance)->toBe('1500000.50')
        ->and($wallet->current_balance)->toBe('1500000.50');
});

// AC-05.2
it('rejects a duplicate wallet name for the same user', function () {
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->create(['name' => 'BCA']);

    $this->actingAs($user)
        ->post('/wallets', walletPayload())
        ->assertSessionHasErrors('name');
});

it('allows the same wallet name for a different user', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    Wallet::factory()->forUser($userA)->create(['name' => 'BCA']);

    $this->actingAs($userB)
        ->post('/wallets', walletPayload())
        ->assertSessionHasNoErrors();
});

it('allows reusing the name of a soft-deleted wallet', function () {
    $user = User::factory()->create();
    $old = Wallet::factory()->forUser($user)->create(['name' => 'BCA']);
    $old->delete();

    $this->actingAs($user)
        ->post('/wallets', walletPayload())
        ->assertSessionHasNoErrors();
});

// AC-05.3
it('rejects invalid initial balances', function (string $balance) {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/wallets', walletPayload(['initial_balance' => $balance]))
        ->assertSessionHasErrors('initial_balance');
})->with(['-1', '10.123', 'abc']);

// AC-05.4
it('hides wallets of other users from direct URL access', function () {
    $userA = User::factory()->create();
    $userB = User::factory()->create();
    $wallet = Wallet::factory()->forUser($userA)->create();

    $this->actingAs($userB)
        ->patch(route('wallets.update', $wallet->id), walletPayload())
        ->assertNotFound();

    $this->actingAs($userB)
        ->delete(route('wallets.destroy', $wallet->id))
        ->assertNotFound();

    $this->actingAs($userB)
        ->post(route('wallets.archive', $wallet->id))
        ->assertNotFound();
});

// AC-06.1
it('updates name, type, color, and icon without touching balances', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('250000.00')->create();

    $this->actingAs($user)
        ->patch(route('wallets.update', $wallet->id), [
            'name' => 'Mandiri',
            'type' => 'bank',
            'color' => '#B94A48',
            'icon' => 'credit-card',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $wallet->refresh();
    expect($wallet->name)->toBe('Mandiri')
        ->and($wallet->color)->toBe('#B94A48')
        ->and($wallet->icon)->toBe('credit-card')
        ->and($wallet->current_balance)->toBe('250000.00')
        ->and($wallet->initial_balance)->toBe('250000.00');
});

// AC-06.2
it('archives and unarchives a wallet', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();

    $this->actingAs($user)->post(route('wallets.archive', $wallet->id), ['archived' => true]);
    expect($wallet->refresh()->is_archived)->toBeTrue();

    $this->actingAs($user)->post(route('wallets.archive', $wallet->id), ['archived' => false]);
    expect($wallet->refresh()->is_archived)->toBeFalse();
});

// AC-06.3
it('soft deletes a wallet without transactions or recurring', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->delete(route('wallets.destroy', $wallet->id))
        ->assertRedirect();

    $this->assertSoftDeleted('wallets', ['id' => $wallet->id]);
});

// AC-06.4
it('refuses to delete a wallet that has transactions', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->create();

    DB::table('transactions')->insert([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => '10000.00',
        'occurred_on' => '2026-07-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->from(route('wallets.index'))
        ->delete(route('wallets.destroy', $wallet->id))
        ->assertRedirect(route('wallets.index'));

    expect(session('error'))->toContain('Arsipkan');
    $this->assertNotSoftDeleted('wallets', ['id' => $wallet->id]);
});

// AC-06.4: termasuk sebagai tujuan transfer.
it('refuses to delete a wallet that is a transfer destination', function () {
    $user = User::factory()->create();
    $source = Wallet::factory()->forUser($user)->create();
    $destination = Wallet::factory()->forUser($user)->create();

    DB::table('transactions')->insert([
        'user_id' => $user->id,
        'wallet_id' => $source->id,
        'destination_wallet_id' => $destination->id,
        'type' => 'transfer',
        'amount' => '5000.00',
        'occurred_on' => '2026-07-01',
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('wallets.destroy', $destination->id));

    $this->assertNotSoftDeleted('wallets', ['id' => $destination->id]);
});

it('refuses to delete a wallet with an active recurring transaction', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->create();

    DB::table('recurring_transactions')->insert([
        'user_id' => $user->id,
        'wallet_id' => $wallet->id,
        'category_id' => $category->id,
        'type' => 'expense',
        'amount' => '50000.00',
        'frequency' => 'monthly',
        'interval' => 1,
        'next_run_on' => '2026-08-01',
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $this->actingAs($user)
        ->delete(route('wallets.destroy', $wallet->id));

    $this->assertNotSoftDeleted('wallets', ['id' => $wallet->id]);
});

// Onboarding (00-PRD.md §7)
it('redirects a verified user without wallets to the first-wallet screen', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertRedirect(route('wallets.first', absolute: false));
});

it('lets a user with a wallet reach the dashboard', function () {
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk();
});
