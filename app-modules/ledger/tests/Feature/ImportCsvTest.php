<?php

declare(strict_types=1);

use Illuminate\Http\UploadedFile;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Infrastructure\Models\Wallet;

function importSetup(): array
{
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->withBalance('1000.00')->create(['name' => 'BCA']);
    $food = Category::factory()->forUser($user)->expense()->create(['name' => 'Makan & Minum']);
    $salary = Category::factory()->forUser($user)->income()->create(['name' => 'Gaji']);

    return [$user, $wallet, $food, $salary];
}

// AC-22.1: template CSV siap unduh dengan header yang benar.
it('downloads the csv import template with the correct header', function () {
    [$user] = importSetup();

    $response = $this->actingAs($user)->get('/imports/template');

    $response->assertOk();
    $content = $response->streamedContent();
    $firstLine = strtok($content, "\n");
    expect(trim((string) $firstLine))->toBe('tanggal,tipe,kategori,dompet,nominal,catatan');
});

// AC-22.1: upload valid mengimpor semua baris dan memperbarui saldo dompet.
it('imports valid csv rows and updates the wallet balance', function () {
    [$user, $wallet, $food, $salary] = importSetup();

    $csv = <<<'CSV'
    tanggal,tipe,kategori,dompet,nominal,catatan
    2026-07-01,pengeluaran,Makan & Minum,BCA,25000.50,Makan siang
    2026-07-02,pemasukan,Gaji,BCA,500000.00,Gaji bulanan
    CSV;

    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

    $response = $this->actingAs($user)->post('/imports/transactions', ['file' => $file]);

    $response->assertRedirect()->assertSessionHas('import_result');
    $result = session('import_result');
    expect($result['imported'])->toBe(2)
        ->and($result['failed'])->toBe([]);

    expect(Transaction::where('user_id', $user->id)->count())->toBe(2);

    // Saldo: 1000.00 - 25000.50 + 500000.00 = 475999.50
    $wallet->refresh();
    expect($wallet->current_balance)->toBe('475999.50');
});

// AC-22.2: baris tidak valid dilewati, hanya baris valid masuk, per baris atomic.
it('skips invalid rows while importing the valid ones', function () {
    [$user, $wallet, $food] = importSetup();

    $tomorrow = now('Asia/Jakarta')->addDay()->toDateString();

    $csv = <<<CSV
    tanggal,tipe,kategori,dompet,nominal,catatan
    2026-07-01,pengeluaran,Makan & Minum,BCA,25000.50,Makan siang
    2026-07-02,pengeluaran,Kategori Tidak Ada,BCA,10000.00,Tidak ada kategori
    2026-07-03,pengeluaran,Makan & Minum,BCA,abc,Nominal salah
    {$tomorrow},pengeluaran,Makan & Minum,BCA,5000.00,Tanggal masa depan
    2026-07-04,pengeluaran,Makan & Minum,BCA,15000.00,Baris valid kedua
    CSV;

    $file = UploadedFile::fake()->createWithContent('import.csv', $csv);

    $response = $this->actingAs($user)->post('/imports/transactions', ['file' => $file]);

    $response->assertRedirect();
    $result = session('import_result');

    expect($result['imported'])->toBe(2)
        ->and($result['failed'])->toHaveCount(3);

    $lines = collect($result['failed'])->pluck('line')->all();
    expect($lines)->toBe([3, 4, 5]);

    expect(Transaction::where('user_id', $user->id)->count())->toBe(2);
});

// AC-22.3: file bukan CSV ditolak.
it('rejects a non-csv file before processing', function () {
    [$user] = importSetup();

    $file = UploadedFile::fake()->create('import.pdf', 10, 'application/pdf');

    $this->actingAs($user)
        ->post('/imports/transactions', ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Transaction::where('user_id', $user->id)->count())->toBe(0);
});

// AC-22.3: file > 2MB ditolak.
it('rejects a csv file larger than 2MB', function () {
    [$user] = importSetup();

    $file = UploadedFile::fake()->create('big.csv', 3000, 'text/csv');

    $this->actingAs($user)
        ->post('/imports/transactions', ['file' => $file])
        ->assertSessionHasErrors('file');

    expect(Transaction::where('user_id', $user->id)->count())->toBe(0);
});
