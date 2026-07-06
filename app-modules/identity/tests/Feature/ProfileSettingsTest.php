<?php

declare(strict_types=1);

use Modules\Identity\Infrastructure\Models\User;
use Modules\Wallet\Infrastructure\Models\Wallet;

function settingsUser(): User
{
    $user = User::factory()->create();
    Wallet::factory()->forUser($user)->create();

    return $user;
}

// AC-12.1
it('updates the profile name', function () {
    $user = settingsUser();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => 'Radit Baru',
            'email' => $user->email,
            'currency' => 'IDR',
        ])
        ->assertRedirect(route('profile.edit', absolute: false))
        ->assertSessionHasNoErrors();

    expect($user->fresh()->name)->toBe('Radit Baru');
});

// AC-12.2: mata uang hanya mengubah format, nilai tidak dikonversi.
it('updates the account currency without converting values', function () {
    $user = settingsUser();
    $wallet = Wallet::withoutGlobalScopes()->where('user_id', $user->id)->firstOrFail();
    $wallet->forceFill(['current_balance' => '125000.50', 'initial_balance' => '125000.50'])->save();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'currency' => 'USD',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->currency)->toBe('USD')
        ->and($wallet->fresh()->current_balance)->toBe('125000.50');
});

it('rejects an unsupported currency', function () {
    $user = settingsUser();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'email' => $user->email,
            'currency' => 'BTC',
        ])
        ->assertSessionHasErrors('currency');
});

it('marks the e-mail unverified again after changing it', function () {
    $user = settingsUser();

    $this->actingAs($user)
        ->patch('/settings/profile', [
            'name' => $user->name,
            'email' => 'baru@contoh.com',
            'currency' => 'IDR',
        ])
        ->assertSessionHasNoErrors();

    expect($user->fresh()->email)->toBe('baru@contoh.com')
        ->and($user->fresh()->email_verified_at)->toBeNull();
});

it('renders every settings page', function (string $url) {
    $user = settingsUser();

    $this->actingAs($user)->get($url)->assertOk();
})->with(['/settings/profile', '/settings/password', '/settings/appearance', '/settings/account', '/categories']);
