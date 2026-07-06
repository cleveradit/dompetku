<?php

declare(strict_types=1);

namespace App\Actions\Fortify;

use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Laravel\Fortify\Contracts\UpdatesUserProfileInformation;
use Modules\Identity\Application\Actions\UpdateProfile;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Shared\Support\Currencies;

class UpdateUserProfileInformation implements UpdatesUserProfileInformation
{
    public function __construct(private readonly UpdateProfile $updateProfile) {}

    /**
     * Update profil: nama, email, mata uang tampilan (US-12).
     *
     * @param  array<string, string>  $input
     *
     * @throws ValidationException
     */
    public function update(User $user, array $input): void
    {
        $validated = Validator::make($input, [
            'name' => ['required', 'string', 'max:100'],
            'email' => [
                'required',
                'string',
                'email:rfc',
                'max:255',
                Rule::unique('users')->ignore($user->id),
            ],
            'currency' => ['sometimes', 'required', 'string', Rule::in(Currencies::SUPPORTED)],
        ])->validateWithBag('updateProfileInformation');

        $this->updateProfile->handle($user, $validated);
    }
}
