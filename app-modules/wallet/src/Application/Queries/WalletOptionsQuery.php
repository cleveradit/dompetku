<?php

declare(strict_types=1);

namespace Modules\Wallet\Application\Queries;

use Modules\Wallet\Infrastructure\Models\Wallet;

/**
 * Data dompet milik user untuk kebutuhan modul lain (pilihan form, label).
 * Jalur resmi lintas modul: Query, bukan relasi Eloquent (01-ARCHITECTURE.md §2).
 */
class WalletOptionsQuery
{
    /**
     * @return list<array{id: int, name: string, color: string|null, icon: string|null, is_archived: bool, current_balance: string}>
     */
    public function active(int $userId): array
    {
        return $this->query($userId)
            ->where('is_archived', false)
            ->get()
            ->map(fn (Wallet $wallet): array => $this->present($wallet))
            ->values()
            ->all();
    }

    /**
     * @return list<array{id: int, name: string, color: string|null, icon: string|null, is_archived: bool, current_balance: string}>
     */
    public function all(int $userId): array
    {
        return $this->query($userId)
            ->get()
            ->map(fn (Wallet $wallet): array => $this->present($wallet))
            ->values()
            ->all();
    }

    /** @return \Illuminate\Database\Eloquent\Builder<Wallet> */
    private function query(int $userId)
    {
        return Wallet::withoutGlobalScopes()
            ->whereNull('deleted_at')
            ->where('user_id', $userId)
            ->orderBy('name');
    }

    /** @return array{id: int, name: string, color: string|null, icon: string|null, is_archived: bool, current_balance: string} */
    private function present(Wallet $wallet): array
    {
        return [
            'id' => $wallet->id,
            'name' => $wallet->name,
            'color' => $wallet->color,
            'icon' => $wallet->icon,
            'is_archived' => $wallet->is_archived,
            'current_balance' => $wallet->current_balance,
        ];
    }
}
