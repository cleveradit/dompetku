<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Actions;

use Modules\Wallet\Infrastructure\Models\Wallet;

class ArchiveWallet
{
    /**
     * AC-06.2: dompet arsip hilang dari pilihan transaksi baru, tetapi saldo
     * dan historinya tetap tampil, dan bisa di-unarchive.
     */
    public function handle(Wallet $wallet, bool $archived = true): Wallet
    {
        $wallet->is_archived = $archived;
        $wallet->save();

        return $wallet;
    }
}
