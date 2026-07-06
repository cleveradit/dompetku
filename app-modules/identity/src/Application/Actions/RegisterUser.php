<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Events\UserRegistered;
use Modules\Identity\Infrastructure\Models\User;

class RegisterUser
{
    public function handle(string $name, string $email, string $password, string $currency): User
    {
        $user = DB::transaction(fn (): User => User::create([
            'name' => $name,
            'email' => $email,
            'password' => $password,
            'currency' => $currency,
        ]));

        UserRegistered::dispatch($user->id);

        return $user;
    }
}
