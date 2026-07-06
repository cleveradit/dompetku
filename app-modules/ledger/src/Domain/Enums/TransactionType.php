<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Enums;

enum TransactionType: string
{
    case Income = 'income';
    case Expense = 'expense';
    case Transfer = 'transfer';
}
