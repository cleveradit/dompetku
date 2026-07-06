<?php

declare(strict_types=1);

namespace Modules\Wallet\Presentation\Console;

use Illuminate\Console\Command;
use Modules\Wallet\Application\Actions\RecalculateWalletBalance;
use Modules\Wallet\Infrastructure\Models\Wallet;

class RecalculateWalletsCommand extends Command
{
    protected $signature = 'wallets:recalculate {--user= : Only recalculate wallets of this user id}';

    protected $description = 'Recalculate cached wallet balances from the transactions table (04-NFR.md R-2)';

    public function handle(RecalculateWalletBalance $recalculate): int
    {
        $query = Wallet::withoutGlobalScopes()->withTrashed();

        if ($this->option('user') !== null) {
            $query->where('user_id', (int) $this->option('user'));
        }

        $count = 0;

        foreach ($query->lazyById() as $wallet) {
            $recalculate->handle($wallet);
            $count++;
        }

        $this->info("Recalculated {$count} wallet(s).");

        return self::SUCCESS;
    }
}
