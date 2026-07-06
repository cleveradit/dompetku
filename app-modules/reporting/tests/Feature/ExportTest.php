<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Maatwebsite\Excel\Facades\Excel;
use Modules\Identity\Infrastructure\Models\User;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Reporting\Application\Exports\TransactionsExport;
use Modules\Reporting\Application\Jobs\MarkTransactionsExportReady;
use Modules\Reporting\Presentation\Http\Controllers\ExportController;
use Modules\Wallet\Infrastructure\Models\Wallet;

// AC-19.1: export CSV terfilter berisi hanya baris sesuai filter.
it('downloads a filtered csv export directly when under the queue threshold', function () {
    Excel::fake();

    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $expense = Category::factory()->forUser($user)->expense()->create();
    $income = Category::factory()->forUser($user)->income()->create();

    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($expense)->count(3)->create();
    Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($income)->create(['type' => 'income']);

    $this->actingAs($user)
        ->get('/exports/transactions?'.http_build_query(['format' => 'csv', 'categories' => [$expense->id]]))
        ->assertOk();

    Excel::assertDownloaded('transaksi-'.now()->format('YmdHis').'.csv', function (TransactionsExport $export) {
        return $export->query()->count() === 3;
    });
});

// AC-19.2: melebihi ambang antrean diproses lewat queue, bukan diunduh langsung.
it('queues the export and marks it ready once the job runs when over the threshold', function () {
    Excel::fake();

    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create();

    $total = ExportController::QUEUE_THRESHOLD + 1;
    $now = now()->toDateTimeString();

    foreach (array_chunk(range(1, $total), 1000) as $chunk) {
        DB::table('transactions')->insert(array_map(fn (int $i) => [
            'user_id' => $user->id,
            'wallet_id' => $wallet->id,
            'category_id' => $category->id,
            'type' => 'expense',
            'amount' => '10.00',
            'occurred_on' => '2026-07-01',
            'created_at' => $now,
            'updated_at' => $now,
        ], $chunk));
    }

    $this->actingAs($user)
        ->get('/exports/transactions?format=csv')
        ->assertRedirect()
        ->assertSessionHas('success');

    Excel::matchByRegex();
    Excel::assertQueued('#^exports/'.$user->id.'/#', 'local', function (): bool {
        return true;
    });

    $cached = Cache::get("transactions-export:{$user->id}");
    expect($cached['status'])->toBe('processing');

    $path = "exports/{$user->id}/some-file.csv";
    (new MarkTransactionsExportReady($user->id, $path, 'transaksi-20260706.csv'))->handle();

    $ready = Cache::get("transactions-export:{$user->id}");
    expect($ready['status'])->toBe('ready')
        ->and($ready['path'])->toBe($path)
        ->and($ready['name'])->toBe('transaksi-20260706.csv');
});

// AC-19.3: nominal string dipertahankan tepat tanpa pembulatan.
it('maps the exact decimal amount without rounding', function () {
    $user = User::factory()->create();
    $wallet = Wallet::factory()->forUser($user)->create();
    $category = Category::factory()->forUser($user)->expense()->create();
    $transaction = Transaction::factory()->forUser($user)->inWallet($wallet)->withCategory($category)->create([
        'amount' => '10500.75',
    ]);

    $export = new TransactionsExport($user->id, [
        'q' => '', 'start' => null, 'end' => null, 'categories' => [],
        'wallet' => null, 'type' => null, 'min' => null, 'max' => null,
    ]);

    $row = $export->query()->first();
    $mapped = $export->map($row);

    expect($mapped[5])->toBe('10500.75')
        ->and($transaction->amount)->toBe('10500.75');
});
