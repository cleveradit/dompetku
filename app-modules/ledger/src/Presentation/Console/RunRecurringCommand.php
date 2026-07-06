<?php

declare(strict_types=1);

namespace Modules\Ledger\Presentation\Console;

use Illuminate\Console\Command;
use Modules\Ledger\Application\Actions\RunDueRecurringTransactions;

class RunRecurringCommand extends Command
{
    protected $signature = 'recurring:run';

    protected $description = 'Create real transactions for due recurring transactions (idempotent, with catch-up)';

    public function handle(RunDueRecurringTransactions $runDue): int
    {
        $created = $runDue->handle();

        $this->info("Created {$created} transaction(s) from recurring schedules.");

        return self::SUCCESS;
    }
}
