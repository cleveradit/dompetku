<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Listeners;

use Modules\Identity\Domain\Events\UserRegistered;
use Modules\Ledger\Application\Actions\SeedDefaultCategories;

class SeedDefaultCategoriesOnRegistration
{
    public function __construct(private readonly SeedDefaultCategories $seedDefaultCategories) {}

    public function handle(UserRegistered $event): void
    {
        $this->seedDefaultCategories->handle($event->userId);
    }
}
