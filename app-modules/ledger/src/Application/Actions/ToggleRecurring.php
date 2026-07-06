<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Modules\Ledger\Infrastructure\Models\RecurringTransaction;

class ToggleRecurring
{
    /**
     * AC-16.4: saat dijeda tidak ada transaksi tercipta; saat diaktifkan lagi
     * next_run_on maju ke jatuh tempo berikutnya di masa depan — masa jeda
     * tidak dirapel.
     */
    public function handle(RecurringTransaction $recurring, bool $active): RecurringTransaction
    {
        if ($active) {
            $today = now('Asia/Jakarta')->toImmutable()->startOfDay();
            $nextRun = $recurring->next_run_on;

            while ($nextRun->lessThan($today)) {
                $nextRun = $recurring->frequency->advance($nextRun, $recurring->interval);
            }

            $recurring->next_run_on = $nextRun;
        }

        $recurring->is_active = $active;
        $recurring->save();

        return $recurring;
    }
}
