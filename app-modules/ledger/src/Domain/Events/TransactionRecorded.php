<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

class TransactionRecorded
{
    use Dispatchable;

    public function __construct(public int $transactionId, public int $userId) {}
}
