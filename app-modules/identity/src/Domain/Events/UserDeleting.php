<?php

declare(strict_types=1);

namespace Modules\Identity\Domain\Events;

use Illuminate\Foundation\Events\Dispatchable;

class UserDeleting
{
    use Dispatchable;

    public function __construct(public int $userId) {}
}
