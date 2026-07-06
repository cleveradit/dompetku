<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Queries;

use Illuminate\Support\Facades\DB;
use Modules\Shared\ValueObjects\DatePeriod;

/** Breakdown pengeluaran & pemasukan per dompet (F-08). */
class SpendingByWalletQuery
{
    /** @return list<array{id: int, name: string, color: string|null, expense: string, income: string}> */
    public function handle(int $userId, DatePeriod $period): array
    {
        return DB::table('transactions')
            ->join('wallets', 'wallets.id', '=', 'transactions.wallet_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->whereIn('transactions.type', ['income', 'expense'])
            ->whereBetween('transactions.occurred_on', [$period->start->toDateString(), $period->end->toDateString()])
            ->selectRaw(<<<'SQL'
                wallets.id AS id,
                wallets.name AS name,
                wallets.color AS color,
                COALESCE(SUM(CASE WHEN transactions.type = 'expense' THEN transactions.amount ELSE 0 END), 0) AS expense,
                COALESCE(SUM(CASE WHEN transactions.type = 'income' THEN transactions.amount ELSE 0 END), 0) AS income
            SQL)
            ->groupBy('wallets.id', 'wallets.name', 'wallets.color')
            ->orderByDesc('expense')
            ->get()
            ->map(fn (object $row): array => [
                'id' => (int) $row->id,
                'name' => (string) $row->name,
                'color' => $row->color !== null ? (string) $row->color : null,
                'expense' => (string) $row->expense,
                'income' => (string) $row->income,
            ])
            ->values()
            ->all();
    }
}
