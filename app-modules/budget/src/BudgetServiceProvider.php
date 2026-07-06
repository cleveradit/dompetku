<?php

declare(strict_types=1);

namespace Modules\Budget;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Budget\Infrastructure\Models\Budget;
use Modules\Budget\Infrastructure\Policies\BudgetPolicy;

class BudgetServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::policy(Budget::class, BudgetPolicy::class);
    }
}
