<?php

declare(strict_types=1);

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in('Feature');

pest()->extend(TestCase::class)
    ->use(RefreshDatabase::class)
    ->in(
        '../app-modules/identity/tests',
        '../app-modules/wallet/tests',
        '../app-modules/ledger/tests',
        '../app-modules/budget/tests',
        '../app-modules/reporting/tests',
    );
