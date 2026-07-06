<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Actions;

use Illuminate\Support\Facades\Auth;
use Modules\Identity\Infrastructure\Models\User;

class ChangePassword
{
    /**
     * Update the user's password, keep the current session alive, and
     * force-logout every other session (04-NFR.md S-3, AC-04.1).
     * The current password has been validated by the caller.
     */
    public function handle(User $user, string $newPassword): void
    {
        $user->forceFill(['password' => $newPassword])->save();

        if (Auth::check() && Auth::id() === $user->id) {
            Auth::logoutOtherDevices($newPassword);
        }
    }
}
