<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Actions;

use Modules\Wallet\Infrastructure\Models\Wallet;

class UpdateWallet
{
    /**
     * AC-06.1: mengubah nama/tipe/warna/ikon tanpa mempengaruhi saldo dan
     * transaksi — saldo tidak pernah diubah lewat aksi ini.
     *
     * @param  array{name?: string, type?: mixed, color?: string|null, icon?: string|null}  $attributes
     */
    public function handle(Wallet $wallet, array $attributes): Wallet
    {
        $wallet->fill([
            'name' => $attributes['name'] ?? $wallet->name,
            'type' => $attributes['type'] ?? $wallet->type,
            'color' => array_key_exists('color', $attributes) ? $attributes['color'] : $wallet->color,
            'icon' => array_key_exists('icon', $attributes) ? $attributes['icon'] : $wallet->icon,
        ]);

        $wallet->save();

        return $wallet;
    }
}
