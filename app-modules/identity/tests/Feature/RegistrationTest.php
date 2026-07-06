<?php

declare(strict_types=1);

use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Support\Facades\Notification;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Radit',
        'email' => 'radit@contoh.com',
        'password' => 'rahasia-123',
        'password_confirmation' => 'rahasia-123',
        'currency' => 'IDR',
    ], $overrides);
}

it('renders the registration screen', function () {
    get('/register')->assertOk();
});

// AC-01.1
it('registers a user, sends verification e-mail, and seeds 12 default categories', function () {
    Notification::fake();

    $response = post('/register', validRegistrationPayload());

    $response->assertRedirect(route('dashboard', absolute: false));
    assertAuthenticated();

    $user = User::where('email', 'radit@contoh.com')->firstOrFail();
    expect($user->currency)->toBe('IDR')
        ->and($user->email_verified_at)->toBeNull();

    Notification::assertSentTo($user, VerifyEmail::class);

    $categories = Category::withoutGlobalScopes()->where('user_id', $user->id)->get();
    expect($categories)->toHaveCount(12)
        ->and($categories->where('type.value', 'expense'))->toHaveCount(8)
        ->and($categories->where('type.value', 'income'))->toHaveCount(4)
        ->and($categories->every(fn ($category) => $category->is_default))->toBeTrue();
});

// AC-01.2
it('rejects an already registered e-mail', function () {
    User::factory()->create(['email' => 'radit@contoh.com']);

    $response = post('/register', validRegistrationPayload());

    $response->assertSessionHasErrors('email');
    expect(User::where('email', 'radit@contoh.com')->count())->toBe(1);
});

// AC-01.3
it('rejects a password shorter than 8 characters', function () {
    post('/register', validRegistrationPayload([
        'password' => 'pendek1',
        'password_confirmation' => 'pendek1',
    ]))->assertSessionHasErrors('password');

    assertGuest();
});

// AC-01.3
it('rejects a mismatched password confirmation', function () {
    post('/register', validRegistrationPayload([
        'password_confirmation' => 'berbeda-123',
    ]))->assertSessionHasErrors('password');

    assertGuest();
});

it('rejects an unsupported currency', function () {
    post('/register', validRegistrationPayload(['currency' => 'XYZ']))
        ->assertSessionHasErrors('currency');
});

// AC-01.4
it('redirects unverified users to the verification notice', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->get('/dashboard')
        ->assertRedirect(route('verification.notice', absolute: false));
});

// AC-01.4: kirim ulang dibatasi 1x per menit.
it('throttles resending the verification e-mail to once per minute', function () {
    Notification::fake();
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)->post(route('verification.send'))->assertRedirect();
    $this->actingAs($user)->post(route('verification.send'))->assertStatus(429);
});

// AC-01.5
it('verifies the e-mail through the signed link', function () {
    $user = User::factory()->unverified()->create();

    $url = \Illuminate\Support\Facades\URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1((string) $user->email),
    ]);

    $this->actingAs($user)->get($url)->assertRedirect();

    expect($user->fresh()->email_verified_at)->not->toBeNull();
});
