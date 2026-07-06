<?php

declare(strict_types=1);

namespace Modules\Wallet;

use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Modules\Wallet\Infrastructure\Models\Wallet;
use Modules\Wallet\Infrastructure\Policies\WalletPolicy;
use Modules\Wallet\Presentation\Console\RecalculateWalletsCommand;

class WalletServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->loadMigrationsFrom(__DIR__.'/../database/migrations');

        Gate::policy(Wallet::class, WalletPolicy::class);

        if ($this->app->runningInConsole()) {
            $this->commands([RecalculateWalletsCommand::class]);
        }
    }
}
