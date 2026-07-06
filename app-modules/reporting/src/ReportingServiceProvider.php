<?php

declare(strict_types=1);

namespace Modules\Reporting;

use Illuminate\Support\ServiceProvider;

class ReportingServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Reporting is read-only: no tables, no migrations.
    }
}
