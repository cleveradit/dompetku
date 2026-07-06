<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(Tests\TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(
        '../app-modules/identity/tests',
        '../app-modules/wallet/tests',
        '../app-modules/ledger/tests',
        '../app-modules/budget/tests',
        '../app-modules/reporting/tests',
    );
