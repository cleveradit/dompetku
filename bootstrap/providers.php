<?php

return [
    App\Providers\AppServiceProvider::class,
    App\Providers\FortifyServiceProvider::class,
    Modules\Budget\BudgetServiceProvider::class,
    Modules\Identity\IdentityServiceProvider::class,
    Modules\Ledger\LedgerServiceProvider::class,
    Modules\Reporting\ReportingServiceProvider::class,
    Modules\Wallet\WalletServiceProvider::class,
];
