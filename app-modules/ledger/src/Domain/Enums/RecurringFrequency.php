<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Enums;

use Carbon\CarbonImmutable;

enum RecurringFrequency: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';

    public function advance(CarbonImmutable $date, int $interval): CarbonImmutable
    {
        return match ($this) {
            self::Daily => $date->addDays($interval),
            self::Weekly => $date->addWeeks($interval),
            self::Monthly => $date->addMonthsNoOverflow($interval),
            self::Yearly => $date->addYearsNoOverflow($interval),
        };
    }
}
