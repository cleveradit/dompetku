<?php

use App\Providers\AppServiceProvider;
use App\Providers\FortifyServiceProvider;
use Modules\Budget\BudgetServiceProvider;
use Modules\Identity\IdentityServiceProvider;
use Modules\Ledger\LedgerServiceProvider;
use Modules\Reporting\ReportingServiceProvider;
use Modules\Wallet\WalletServiceProvider;

return [
    AppServiceProvider::class,
    FortifyServiceProvider::class,
    BudgetServiceProvider::class,
    IdentityServiceProvider::class,
    LedgerServiceProvider::class,
    ReportingServiceProvider::class,
    WalletServiceProvider::class,
];
