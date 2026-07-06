<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Enums;

enum WalletType: string
{
    case Cash = 'cash';
    case Bank = 'bank';
    case Ewallet = 'ewallet';
    case Other = 'other';

    public function label(): string
    {
        return match ($this) {
            self::Cash => 'Tunai',
            self::Bank => 'Bank',
            self::Ewallet => 'E-wallet',
            self::Other => 'Lainnya',
        };
    }
}
