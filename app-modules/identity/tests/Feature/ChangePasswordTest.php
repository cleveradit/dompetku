<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Hash;
use Modules\Identity\Infrastructure\Models\User;

// AC-04.1
it('changes the password with the correct current password', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/user/password', [
            'current_password' => 'password',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect();

    expect(Hash::check('password-baru-123', $user->fresh()->password))->toBeTrue();
    $this->assertAuthenticatedAs($user);
});

// AC-04.2
it('rejects a wrong current password and changes nothing', function () {
    $user = User::factory()->create();
    $originalHash = $user->password;

    $this->actingAs($user)
        ->from('/settings/password')
        ->put('/user/password', [
            'current_password' => 'bukan-password',
            'password' => 'password-baru-123',
            'password_confirmation' => 'password-baru-123',
        ])
        ->assertSessionHasErrorsIn('updatePassword', ['current_password']);

    expect($user->fresh()->password)->toBe($originalHash);
});
