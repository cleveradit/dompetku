<?php

declare(strict_types=1);

use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

function transferSetup(): array
{
    $user = User::factory()->create();
    $source = Wallet::factory()->forUser($user)->withBalance('100.00')->create();
    $destination = Wallet::factory()->forUser($user)->withBalance('20.00')->create();

    return [$user, $source, $destination];
}

// AC-08.1
it('moves money between wallets with a single transfer record', function () {
    [$user, $source, $destination] = transferSetup();

    $this->actingAs($user)
        ->post('/transfers', [
            'wallet_id' => $source->id,
            'destination_wallet_id' => $destination->id,
            'amount' => '30.25',
            'occurred_on' => '2026-07-01',
        ])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    expect($source->refresh()->current_balance)->toBe('69.75')
        ->and($destination->refresh()->current_balance)->toBe('50.25');

    $transactions = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->get();
    expect($transactions)->toHaveCount(1)
        ->and($transactions->first()->type->value)->toBe('transfer')
        ->and($transactions->first()->category_id)->toBeNull();
});

// AC-08.2
it('rejects a transfer to the same wallet', function () {
    [$user, $source] = transferSetup();

    $this->actingAs($user)
        ->post('/transfers', [
            'wallet_id' => $source->id,
            'destination_wallet_id' => $source->id,
            'amount' => '10.00',
            'occurred_on' => '2026-07-01',
        ])
        ->assertSessionHasErrors('destination_wallet_id');
});

it('rejects a transfer to another user\'s wallet', function () {
    [$user, $source] = transferSetup();
    $foreignWallet = Wallet::factory()->create();

    $this->actingAs($user)
        ->post('/transfers', [
            'wallet_id' => $source->id,
            'destination_wallet_id' => $foreignWallet->id,
            'amount' => '10.00',
            'occurred_on' => '2026-07-01',
        ])
        ->assertSessionHasErrors('destination_wallet_id');
});

// AC-08.4
it('restores both balances when a transfer is deleted', function () {
    [$user, $source, $destination] = transferSetup();

    $this->actingAs($user)->post('/transfers', [
        'wallet_id' => $source->id,
        'destination_wallet_id' => $destination->id,
        'amount' => '30.00',
        'occurred_on' => '2026-07-01',
    ]);

    $transfer = Transaction::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();

    $this->actingAs($user)->delete("/transactions/{$transfer->id}")->assertRedirect();

    expect($source->refresh()->current_balance)->toBe('100.00')
        ->and($destination->refresh()->current_balance)->toBe('20.00');
});
