<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Attachment;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

function attachmentSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create();
    $transaction = Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create();

    return [$user, $transaction];
}

// AC-18.1: JPG/PNG/WebP/PDF <= 5MB tersimpan di disk privat.
it('stores allowed attachment types on the private disk', function (string $extension, string $mime) {
    Storage::fake('local');
    [$user, $transaction] = attachmentSetup();

    $file = UploadedFile::fake()->create("struk.$extension", 100, $mime);

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", ['file' => $file])
        ->assertRedirect()
        ->assertSessionHasNoErrors();

    $attachment = Attachment::where('transaction_id', $transaction->id)->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);
})->with([
    ['jpg', 'image/jpeg'],
    ['png', 'image/png'],
    ['webp', 'image/webp'],
    ['pdf', 'application/pdf'],
]);

// AC-18.2: file .txt ditolak.
it('rejects disallowed file types', function () {
    Storage::fake('local');
    [$user, $transaction] = attachmentSetup();

    $file = UploadedFile::fake()->create('catatan.txt', 10, 'text/plain');

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Attachment::where('transaction_id', $transaction->id)->count())->toBe(0);
});

// AC-18.2: ukuran > 5MB ditolak.
it('rejects files larger than 5MB', function () {
    Storage::fake('local');
    [$user, $transaction] = attachmentSetup();

    $file = UploadedFile::fake()->create('struk.jpg', 5121, 'image/jpeg');

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Attachment::where('transaction_id', $transaction->id)->count())->toBe(0);
});

// AC-18.3: lampiran ke-6 ditolak.
it('rejects a sixth attachment on the same transaction', function () {
    Storage::fake('local');
    [$user, $transaction] = attachmentSetup();

    foreach (range(1, 5) as $i) {
        $this->actingAs($user)
            ->post("/transactions/{$transaction->id}/attachments", [
                'file' => UploadedFile::fake()->create("struk-$i.jpg", 100, 'image/jpeg'),
            ])
            ->assertSessionHasNoErrors();
    }

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", [
            'file' => UploadedFile::fake()->create('struk-6.jpg', 100, 'image/jpeg'),
        ])
        ->assertRedirect()
        ->assertSessionHas('error');

    expect(Attachment::where('transaction_id', $transaction->id)->count())->toBe(5);
});

// AC-18.4: user lain tidak bisa mengakses lampiran user A.
it('blocks another user from viewing an attachment', function () {
    Storage::fake('local');
    [$user, $transaction] = attachmentSetup();
    $intruder = User::factory()->create();
    Wallet::factory()->forUser($intruder)->create();

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", [
            'file' => UploadedFile::fake()->create('struk.jpg', 100, 'image/jpeg'),
        ]);
    $attachment = Attachment::where('transaction_id', $transaction->id)->firstOrFail();

    $this->actingAs($intruder)
        ->get("/attachments/{$attachment->id}")
        ->assertNotFound();
});

// AC-18.5: hapus lampiran menghapus file fisik.
it('deletes the physical file when an attachment is removed', function () {
    Storage::fake('local');
    [$user, $transaction] = attachmentSetup();

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", [
            'file' => UploadedFile::fake()->create('struk.jpg', 100, 'image/jpeg'),
        ]);
    $attachment = Attachment::where('transaction_id', $transaction->id)->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);

    $this->actingAs($user)
        ->delete("/attachments/{$attachment->id}")
        ->assertRedirect()
        ->assertSessionHas('success');

    Storage::disk('local')->assertMissing($attachment->path);
    expect(Attachment::find($attachment->id))->toBeNull();
});
