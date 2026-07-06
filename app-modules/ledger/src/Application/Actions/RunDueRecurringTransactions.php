<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;
use Modules\Ledger\Infrastructure\Models\Transaction;

/**
 * Generator recurring (AC-16.2/16.3): dijalankan scheduler harian, idempotent
 * (I-11), melakukan catch-up hari terlewat, dan mematikan recurring yang
 * melewati end_on (AC-16.5). Query berbasis index (is_active, next_run_on)
 * dan diproses per chunk (04-NFR.md P-8). Berjalan tanpa auth — scope user
 * eksplisit per baris recurring.
 */
class RunDueRecurringTransactions
{
    public function __construct(private readonly RecordTransaction $recordTransaction) {}

    public function handle(?CarbonImmutable $today = null): int
    {
        $today = ($today ?? now('Asia/Jakarta')->toImmutable())->startOfDay();
        $created = 0;

        RecurringTransaction::withoutGlobalScopes()
            ->where('is_active', true)
            ->where('next_run_on', '<=', $today->toDateString())
            ->orderBy('id')
            ->chunkById(200, function ($chunk) use ($today, &$created): void {
                /** @var RecurringTransaction $recurring */
                foreach ($chunk as $recurring) {
                    $created += $this->runOne($recurring, $today);
                }
            });

        return $created;
    }

    private function runOne(RecurringTransaction $recurring, CarbonImmutable $today): int
    {
        // I-5: dompet terarsip tidak menerima transaksi baru — recurring-nya
        // dilewati (setara jeda) sampai dompet diaktifkan lagi.
        $walletIds = array_filter([$recurring->wallet_id, $recurring->destination_wallet_id]);
        $usableWallets = DB::table('wallets')
            ->whereIn('id', $walletIds)
            ->whereNull('deleted_at')
            ->where('is_archived', false)
            ->count();

        if ($usableWallets !== count($walletIds)) {
            return 0;
        }

        $created = 0;
        $nextRun = $recurring->next_run_on;

        while ($nextRun->lessThanOrEqualTo($today)) {
            if ($recurring->end_on !== null && $nextRun->greaterThan($recurring->end_on)) {
                break;
            }

            // I-11: satu recurring + satu tanggal jatuh tempo = maksimal satu
            // transaksi, walau command dijalankan dua kali.
            $exists = Transaction::withoutGlobalScopes()->withTrashed()
                ->where('recurring_transaction_id', $recurring->id)
                ->whereDate('occurred_on', $nextRun->toDateString())
                ->exists();

            if (! $exists) {
                $this->recordTransaction->handle(
                    userId: $recurring->user_id,
                    type: $recurring->type,
                    walletId: $recurring->wallet_id,
                    destinationWalletId: $recurring->destination_wallet_id,
                    categoryId: $recurring->category_id,
                    amount: $recurring->amount,
                    occurredOn: $nextRun->toDateString(),
                    description: $recurring->description,
                    recurringTransactionId: $recurring->id,
                );
                $created++;
            }

            $recurring->last_run_on = $nextRun;
            $nextRun = $recurring->frequency->advance($nextRun, $recurring->interval);
        }

        $recurring->next_run_on = $nextRun;

        // AC-16.5: berhenti otomatis setelah end_on terlampaui.
        if ($recurring->end_on !== null && $nextRun->greaterThan($recurring->end_on)) {
            $recurring->is_active = false;
        }

        $recurring->save();

        return $created;
    }
}
