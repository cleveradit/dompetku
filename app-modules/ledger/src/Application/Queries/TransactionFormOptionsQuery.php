<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Queries;

use Modules\Ledger\Infrastructure\Models\Category;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Application\Queries\WalletOptionsQuery;

/**
 * Data untuk form transaksi global (FAB): dompet aktif, kategori per tipe,
 * dompet terakhir dipakai (AC-11.3), dan tanggal hari ini Asia/Jakarta.
 */
class TransactionFormOptionsQuery
{
    public function __construct(private readonly WalletOptionsQuery $walletOptions) {}

    /** @return array<string, mixed> */
    public function handle(int $userId): array
    {
        $categories = Category::query()
            ->orderBy('name')
            ->get()
            ->map(fn (Category $category) => [
                'id' => $category->id,
                'name' => $category->name,
                'type' => $category->type->value,
                'color' => $category->color,
                'icon' => $category->icon,
                'is_default' => $category->is_default,
            ]);

        $lastTransaction = Transaction::query()->latest('id')->first();

        return [
            'wallets' => $this->walletOptions->active($userId),
            'categories' => $categories,
            'lastWalletId' => $lastTransaction?->wallet_id,
            'today' => now('Asia/Jakarta')->toDateString(),
        ];
    }
}
