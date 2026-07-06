<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Validation\Rules\Password;

trait PasswordValidationRules
{
    /**
     * Password >= 8 karakter + konfirmasi cocok (AC-01.3).
     *
     * @return array<int, mixed>
     */
    protected function passwordRules(): array
    {
        return ['required', 'string', Password::min(8), 'confirmed'];
    }
}
