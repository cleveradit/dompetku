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
     *
     * `transactions.wallet_id`/`category_id` are ON DELETE RESTRICT (so a
     * wallet/category in normal use can't be deleted out from under a
     * transaction). That means MySQL can't be trusted to cascade sibling
     * FKs of `users` in a safe order, so transactions are deleted explicitly
     * first — this cascades their attachments — before the rest of the
     * user's rows and the user itself are removed.
     */
    public function handle(User $user): void
    {
        UserDeleting::dispatch($user->id);

        DB::transaction(function () use ($user): void {
            DB::table('transactions')->where('user_id', $user->id)->delete();
            $user->delete();
        });
    }
}
