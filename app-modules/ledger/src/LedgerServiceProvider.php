<?php

declare(strict_types=1);

namespace Modules\Ledger;

use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Identity\Domain\Events\UserRegistered;
use Modules\Ledger\Application\Listeners\SeedDefaultCategoriesOnRegistration;
use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\RecurringTransaction;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Ledger\Infrastructure\Policies\CategoryPolicy;
use Modules\Ledger\Infrastructure\Policies\RecurringTransactionPolicy;
use Modules\Ledger\Infrastructure\Policies\TransactionPolicy;
use Modules\Ledger\Presentation\Console\RunRecurringCommand;

class LedgerServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::policy(Transaction::class, TransactionPolicy::class);
        Gate::policy(Category::class, CategoryPolicy::class);
        Gate::policy(RecurringTransaction::class, RecurringTransactionPolicy::class);

        Event::listen(UserRegistered::class, SeedDefaultCategoriesOnRegistration::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RunRecurringCommand::class]);
        }
    }
}
