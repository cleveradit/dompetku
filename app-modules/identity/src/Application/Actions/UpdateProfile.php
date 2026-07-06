<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Actions;

use Modules\Identity\Infrastructure\Models\User;

class UpdateProfile
{
    /**
     * @param  array{name?: string, email?: string, currency?: string}  $attributes
     */
    public function handle(User $user, array $attributes): User
    {
        $user->fill($attributes);

        if ($user->isDirty('email')) {
            $user->email_verified_at = null;
        }

        $user->save();

        if ($user->wasChanged('email')) {
            $user->sendEmailVerificationNotification();
        }

        return $user;
    }
}
