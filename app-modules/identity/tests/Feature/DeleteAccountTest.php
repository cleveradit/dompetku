<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Modules\Budget\Infrastructure\Models\Budget;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Attachment;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

// AC-20.1: hapus akun beserta seluruh data terkait dan file lampiran fisik.
it('deletes the account and all related data including attachment files', function () {
    Storage::fake('local');

    $user = User::factory()->create(['password' => Hash::make('password')]);
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create();
    $transaction = Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create();
    $budget = Budget::factory()->forUser($user)->forCategory($category)->create();
    $recurring = RecurringTransaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create();

    $this->actingAs($user)
        ->post("/transactions/{$transaction->id}/attachments", [
            'file' => UploadedFile::fake()->create('struk.jpg', 100, 'image/jpeg'),
        ])
        ->assertSessionHasNoErrors();
    $attachment = Attachment::where('transaction_id', $transaction->id)->firstOrFail();
    Storage::disk('local')->assertExists($attachment->path);

    $this->actingAs($user)
        ->delete('/settings/account', ['password' => 'password'])
        ->assertRedirect('/sampai-jumpa');

    expect(User::withoutGlobalScopes()->find($user->id))->toBeNull()
        ->and(Wallet::withoutGlobalScopes()->find($wallet->id))->toBeNull()
        ->and(Category::withoutGlobalScopes()->find($category->id))->toBeNull()
        ->and(Transaction::withoutGlobalScopes()->withTrashed()->find($transaction->id))->toBeNull()
        ->and(Budget::withoutGlobalScopes()->find($budget->id))->toBeNull()
        ->and(RecurringTransaction::withoutGlobalScopes()->find($recurring->id))->toBeNull()
        ->and(Attachment::find($attachment->id))->toBeNull();

    Storage::disk('local')->assertMissing($attachment->path);
    $this->assertGuest();
});

// AC-20.2: password salah tidak mengubah data apa pun.
it('rejects account deletion with the wrong password and changes nothing', function () {
    $user = User::factory()->create(['password' => Hash::make('password')]);
    $wallet = Wallet::factory()->forUser($user)->create();

    $this->actingAs($user)
        ->delete('/settings/account', ['password' => 'salah'])
        ->assertSessionHasErrors('password');

    expect(User::withoutGlobalScopes()->find($user->id))->not->toBeNull()
        ->and(Wallet::withoutGlobalScopes()->find($wallet->id))->not->toBeNull();
    $this->assertAuthenticatedAs($user);
});
