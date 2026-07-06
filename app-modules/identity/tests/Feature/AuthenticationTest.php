<?php

declare(strict_types=1);

use Modules\Identity\Infrastructure\Models\User;

use function Pest\Laravel\assertAuthenticated;
use function Pest\Laravel\assertGuest;
use function Pest\Laravel\get;
use function Pest\Laravel\post;

it('renders the login screen', function () {
    get('/login')->assertOk();
});

// AC-02.1
it('logs in a verified user with correct credentials', function () {
    $user = User::factory()->create();

    post('/login', [
        'email' => $user->email,
        'password' => 'password',
    ])->assertRedirect(route('dashboard', absolute: false));

    assertAuthenticated();
});

// AC-02.2
it('shows a generic error for a wrong password', function () {
    $user = User::factory()->create();

    $response = post('/login', [
        'email' => $user->email,
        'password' => 'salah-besar',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(__('auth.failed'));
    assertGuest();
});

// AC-02.2: pesan identik untuk email yang tidak terdaftar (anti enumeration).
it('shows the same generic error for an unknown e-mail', function () {
    $response = post('/login', [
        'email' => 'tidak-terdaftar@contoh.com',
        'password' => 'apapun-123',
    ]);

    $response->assertSessionHasErrors('email');
    expect(session('errors')->first('email'))->toBe(__('auth.failed'));
});

// AC-02.3
it('throttles after 5 failed logins within a minute', function () {
    $user = User::factory()->create();

    foreach (range(1, 5) as $i) {
        post('/login', ['email' => $user->email, 'password' => 'salah-'.$i]);
    }

    post('/login', ['email' => $user->email, 'password' => 'password'])
        ->assertStatus(429);
});

// AC-02.4
it('destroys the session on logout', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->post('/logout')->assertRedirect('/');
    assertGuest();

    get('/dashboard')->assertRedirect(route('login', absolute: false));
});

// AC-02.5
it('redirects guests to login for any app URL', function (string $url) {
    get($url)->assertRedirect(route('login', absolute: false));
})->with(['/dashboard', '/settings/profile', '/settings/password']);
