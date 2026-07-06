<?php

declare(strict_types=1);

namespace Modules\Shared\Support;

/**
 * Server-side mirror of the lucide icon whitelist in resources/js/lib/icons.ts
 * (05-DESIGN.md 2.3). Wallet and category icons must come from this list.
 */
final class Icons
{
    /** @var list<string> */
    public const WHITELIST = [
        'baby', 'banknote', 'bike', 'book-open', 'briefcase', 'building-2',
        'bus', 'car', 'coffee', 'credit-card', 'dumbbell', 'film', 'fuel',
        'gamepad-2', 'gift', 'graduation-cap', 'hand-coins', 'heart',
        'heart-pulse', 'home', 'landmark', 'laptop', 'music', 'paw-print',
        'piggy-bank', 'pill', 'plane', 'receipt', 'shirt', 'shopping-bag',
        'shopping-cart', 'smartphone', 'sparkles', 'trending-up', 'tv',
        'utensils', 'wallet', 'wifi', 'wrench', 'zap',
    ];
}
