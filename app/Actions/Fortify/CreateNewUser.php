<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\CreatesNewUsers;
use Modules\Identity\Application\Actions\RegisterUser;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Shared\Support\Currencies;

class CreateNewUser implements CreatesNewUsers
{
    use PasswordValidationRules;

    public function __construct(private readonly RegisterUser $registerUser)
    {
    }

    /**
     * Validate and create a newly registered user (US-01).
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function create(array $input): User
    {
        Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique(User::class),
            ],
            'password' => $this->passwordRules(),
            'currency' => ['required', 'string', Rule::in(Currencies::SUPPORTED)],
        ])->validate();

        return $this->registerUser->handle(
            name: trim($input['name']),
            email: $input['email'],
            password: $input['password'],
            currency: $input['currency'],
        );
    }
}
