<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Listeners;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Modules\Identity\Domain\Events\UserDeleting;

/**
 * AC-20.1, 04-NFR.md S-13: file lampiran bukan bagian dari database, jadi
 * harus dihapus manual sebelum baris terkait tersapu oleh cascade delete.
 * Menyertakan transaksi yang sudah soft-deleted agar tidak ada file yatim.
 */
class PurgeAttachmentsOnAccountDeletion
{
    public function handle(UserDeleting $event): void
    {
        $paths = DB::table('attachments')
            ->join('transactions', 'transactions.id', '=', 'attachments.transaction_id')
            ->where('transactions.user_id', $event->userId)
            ->pluck('attachments.path');

        foreach ($paths as $path) {
            Storage::disk('local')->delete($path);
        }
    }
}
