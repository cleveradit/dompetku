<?php

declare(strict_types=1);

namespace Modules\Identity\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Identity\Domain\Events\UserDeleting;
use Modules\Identity\Infrastructure\Models\User;

class DeleteAccount
{
    /**
     * Hapus akun beserta SELURUH data (US-20). Password confirmation is the
     * caller's responsibility. Other modules listen to UserDeleting to purge
     * non-database artifacts (e.g. attachment files) before rows cascade.
     */
    public function handle(User $user): void
    {
        UserDeleting::dispatch($user->id);

        DB::transaction(function () use ($user): void {
            $user->delete();
        });
    }
}
