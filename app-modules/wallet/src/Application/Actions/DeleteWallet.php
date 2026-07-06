<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Wallet\Domain\Exceptions\WalletHasTransactions;
use Modules\Wallet\Infrastructure\Models\Wallet;

class DeleteWallet
{
    /**
     * I-7: dompet yang punya transaksi (termasuk sebagai destination, termasuk
     * yang soft-deleted) atau recurring aktif tidak boleh dihapus. Pengecekan
     * memakai query tabel berbasis foreign key ID — bukan relasi Eloquent
     * lintas modul (01-ARCHITECTURE.md §2).
     *
     * @throws WalletHasTransactions
     */
    public function handle(Wallet $wallet): void
    {
        if ($this->isUsed($wallet)) {
            throw WalletHasTransactions::make();
        }

        $wallet->delete();
    }

    public function isUsed(Wallet $wallet): bool
    {
        $hasTransactions = DB::table('transactions')
            ->where(fn ($query) => $query
                ->where('wallet_id', $wallet->id)
                ->orWhere('destination_wallet_id', $wallet->id))
            ->exists();

        if ($hasTransactions) {
            return true;
        }

        return DB::table('recurring_transactions')
            ->where('is_active', true)
            ->where(fn ($query) => $query
                ->where('wallet_id', $wallet->id)
                ->orWhere('destination_wallet_id', $wallet->id))
            ->exists();
    }
}
