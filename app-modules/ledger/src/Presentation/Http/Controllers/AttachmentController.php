<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Http\Controllers;

use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Modules\Ledger\Application\Actions\AttachReceipt;
use Modules\Ledger\Application\Actions\RemoveReceipt;
use Modules\Ledger\Domain\Exceptions\AttachmentLimitReached;
use Modules\Ledger\Infrastructure\Models\Attachment;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AttachmentController extends Controller
{
    use AuthorizesRequests;

    public function store(Request $request, Transaction $transaction, AttachReceipt $attachReceipt): RedirectResponse
    {
        $this->authorize('update', $transaction);

        $request->validate([
            // 04-NFR.md: cek isi via finfo (mimetypes), maks 5 MB.
            'file' => ['required', 'file', 'mimetypes:image/jpeg,image/png,image/webp,application/pdf', 'max:5120'],
        ], [
            'file.mimetypes' => 'Lampiran harus berupa JPG, PNG, WebP, atau PDF.',
            'file.max' => 'Ukuran lampiran maksimal 5 MB.',
        ]);

        try {
            $attachReceipt->handle($transaction, $request->file('file'));
        } catch (AttachmentLimitReached $exception) {
            return back()->with('error', $exception->getMessage());
        }

        return back()->with('success', __('ui.attachment_added'));
    }

    /** 04-NFR.md S-8: file tersaji hanya lewat route ter-otorisasi (streamed). */
    public function show(Request $request, Attachment $attachment): StreamedResponse
    {
        $this->authorizeAttachment($request, $attachment);

        return Storage::disk('local')->response($attachment->path, $attachment->original_name);
    }

    public function destroy(Request $request, Attachment $attachment, RemoveReceipt $removeReceipt): RedirectResponse
    {
        $this->authorizeAttachment($request, $attachment);

        $removeReceipt->handle($attachment);

        return back()->with('success', __('ui.attachment_removed'));
    }

    private function authorizeAttachment(Request $request, Attachment $attachment): void
    {
        $transaction = Transaction::withoutGlobalScopes()->withTrashed()->find($attachment->transaction_id);

        abort_unless($transaction !== null && $transaction->user_id === $request->user()->id, 404);
    }
}
