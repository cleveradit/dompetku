<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Support\Facades\Storage;
use Modules\Ledger\Infrastructure\Models\Attachment;

class RemoveReceipt
{
    /** AC-18.5: file fisik ikut terhapus. */
    public function handle(Attachment $attachment): void
    {
        Storage::disk('local')->delete($attachment->path);

        $attachment->delete();
    }
}
