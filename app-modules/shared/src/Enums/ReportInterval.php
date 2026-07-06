<?php

declare(strict_types=1);

namespace Modules\Shared\Enums;

enum ReportInterval: string
{
    case Daily = 'daily';
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Yearly = 'yearly';
    case Custom = 'custom';
}
