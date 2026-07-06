<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Queries;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Illuminate\Support\Facades\DB;
use Modules\Shared\ValueObjects\DatePeriod;

/**
 * Breakdown pengeluaran per kategori: nominal + persen, terurut terbesar
 * (AC-10.1). Persen dibulatkan hanya untuk tampilan (04-NFR.md M-2).
 */
class SpendingByCategoryQuery
{
    /** @return list<array{id: int|null, name: string, color: string|null, icon: string|null, amount: string, percent: float}> */
    public function handle(int $userId, DatePeriod $period, string $type = 'expense', ?int $walletId = null): array
    {
        $rows = DB::table('transactions')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->where('transactions.type', $type)
            ->whereBetween('transactions.occurred_on', [$period->start->toDateString(), $period->end->toDateString()])
            ->when($walletId !== null, fn ($query) => $query->where('transactions.wallet_id', $walletId))
            ->selectRaw(<<<'SQL'
                transactions.category_id AS id,
                COALESCE(categories.name, 'Tanpa kategori') AS name,
                categories.color AS color,
                categories.icon AS icon,
                SUM(transactions.amount) AS amount
            SQL)
            ->groupBy('transactions.category_id', 'categories.name', 'categories.color', 'categories.icon')
            ->orderByDesc('amount')
            ->get();

        $total = $rows->reduce(
            fn (BigDecimal $carry, object $row) => $carry->plus(BigDecimal::of((string) $row->amount)),
            BigDecimal::of('0'),
        );

        return $rows->map(function (object $row) use ($total): array {
            $amount = BigDecimal::of((string) $row->amount);
            $percent = $total->isZero()
                ? 0.0
                : $amount->multipliedBy(100)->dividedBy($total, 1, RoundingMode::HALF_UP)->toFloat();

            return [
                'id' => $row->id !== null ? (int) $row->id : null,
                'name' => (string) $row->name,
                'color' => $row->color !== null ? (string) $row->color : null,
                'icon' => $row->icon !== null ? (string) $row->icon : null,
                'amount' => (string) $amount->toScale(2),
                'percent' => $percent,
            ];
        })->values()->all();
    }
}
