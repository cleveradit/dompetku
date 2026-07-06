<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Enums;

enum CategoryType: string
{
    case Income = 'income';
    case Expense = 'expense';
}
