<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;
use Modules\Identity\Infrastructure\Models\User;

use function Pest\Laravel\post;

// AC-03.1
it('sends a reset link to a registered e-mail', function () {
    Notification::fake();
    $user = User::factory()->create();

    post('/forgot-password', ['email' => $user->email])->assertRedirect();

    Notification::assertSentTo($user, ResetPassword::class);
});

// AC-03.1: respon UI sama persis untuk email tidak terdaftar (anti enumeration).
it('gives the same response for an unknown e-mail', function () {
    Notification::fake();

    post('/forgot-password', ['email' => 'tidak-ada@contoh.com'])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('passwords.sent'));

    Notification::assertNothingSent();
});

it('gives the identical response for a registered e-mail', function () {
    Notification::fake();
    $user = User::factory()->create();

    post('/forgot-password', ['email' => $user->email])
        ->assertRedirect()
        ->assertSessionHasNoErrors()
        ->assertSessionHas('status', __('passwords.sent'));
});

// AC-03.2
it('resets the password with a valid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'password-baru-123',
        'password_confirmation' => 'password-baru-123',
    ])->assertSessionHasNoErrors();

    post('/login', [
        'email' => $user->email,
        'password' => 'password-baru-123',
    ])->assertRedirect(route('dashboard', absolute: false));
});

// AC-03.3
it('rejects a used or invalid token', function () {
    $user = User::factory()->create();
    $token = Password::createToken($user);

    post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'password-baru-123',
        'password_confirmation' => 'password-baru-123',
    ]);

    post('/reset-password', [
        'token' => $token,
        'email' => $user->email,
        'password' => 'password-lain-456',
        'password_confirmation' => 'password-lain-456',
    ])->assertSessionHasErrors('email');
});
