<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Actions;

use Modules\Wallet\Domain\Enums\WalletType;
use Modules\Wallet\Infrastructure\Models\Wallet;

class CreateWallet
{
    public function handle(
        int $userId,
        string $name,
        WalletType $type,
        string $initialBalance,
        ?string $color,
        ?string $icon,
    ): Wallet {
        return Wallet::create([
            'user_id' => $userId,
            'name' => $name,
            'type' => $type,
            'initial_balance' => $initialBalance,
            'current_balance' => $initialBalance,
            'color' => $color,
            'icon' => $icon,
        ]);
    }
}
