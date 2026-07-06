<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserPasswords;
use Modules\Identity\Application\Actions\ChangePassword;
use Modules\Identity\Infrastructure\Models\User;

class UpdateUserPassword implements UpdatesUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly ChangePassword $changePassword)
    {
    }

    /**
     * Ganti password: wajib password lama (US-04).
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        Validator::make($input, [
            'current_password' => ['required', 'string', 'current_password:web'],
            'password' => $this->passwordRules(),
        ], [
            'current_password.current_password' => __('auth.current_password_mismatch'),
        ])->validateWithBag('updatePassword');

        $this->changePassword->handle($user, $input['password']);
    }
}
