<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\ResetsUserPasswords;
use Modules\Identity\Application\Actions\ChangePassword;
use Modules\Identity\Infrastructure\Models\User;

class ResetUserPassword implements ResetsUserPasswords
{
    use PasswordValidationRules;

    public function __construct(private readonly ChangePassword $changePassword) {}

    /**
     * Reset password via e-mail link; semua session lama ter-invalidate (AC-03.2).
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function reset(User $user, array $input): void
    {
        Validator::make($input, [
            'password' => $this->passwordRules(),
        ])->validate();

        $this->changePassword->handle($user, $input['password']);
    }
}
