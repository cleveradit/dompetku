<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Http\UploadedFile;
use Modules\Ledger\Domain\Exceptions\AttachmentLimitReached;
use Modules\Ledger\Infrastructure\Models\Attachment;
use Modules\Ledger\Infrastructure\Models\Transaction;

class AttachReceipt
{
    /**
     * US-18: file di disk privat, nama acak (04-NFR.md S-8); maksimal 5
     * lampiran per transaksi (I-10).
     *
     * @throws AttachmentLimitReached
     */
    public function handle(Transaction $transaction, UploadedFile $file): Attachment
    {
        if ($transaction->attachments()->count() >= 5) {
            throw AttachmentLimitReached::make();
        }

        $path = $file->store('attachments', 'local');

        return $transaction->attachments()->create([
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => (string) $file->getMimeType(),
            'size_bytes' => $file->getSize(),
        ]);
    }
}
