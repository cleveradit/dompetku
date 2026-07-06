<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Queries;

use Brick\Math\BigDecimal;
use Brick\Math\RoundingMode;
use Carbon\CarbonImmutable;
use Illuminate\Support\Facades\DB;

/**
 * Progres anggaran per kategori (US-15). Reporting yang menghitung progres;
 * Budget hanya menyimpan angka (01-ARCHITECTURE.md §3). Status: ok < 80%,
 * warning >= 80%, danger >= 100% (AC-15.2).
 */
class BudgetProgressQuery
{
    /** @return list<array<string, mixed>> */
    public function handle(int $userId, string $month): array
    {
        $monthStart = CarbonImmutable::parse($month)->startOfMonth();
        $monthEnd = $monthStart->endOfMonth();

        $rows = DB::table('budgets')
            ->join('categories', 'categories.id', '=', 'budgets.category_id')
            ->leftJoin('transactions', function ($join) use ($monthStart, $monthEnd) {
                $join->on('transactions.category_id', '=', 'budgets.category_id')
                    ->where('transactions.type', 'expense')
                    ->whereNull('transactions.deleted_at')
                    ->whereBetween('transactions.occurred_on', [$monthStart->toDateString(), $monthEnd->toDateString()]);
            })
            ->where('budgets.user_id', $userId)
            ->where('budgets.month', $monthStart->toDateString())
            ->selectRaw(<<<'SQL'
                budgets.id AS id,
                budgets.amount AS amount,
                budgets.category_id AS category_id,
                categories.name AS category_name,
                categories.color AS category_color,
                categories.icon AS category_icon,
                COALESCE(SUM(transactions.amount), 0) AS spent
            SQL)
            ->groupBy('budgets.id', 'budgets.amount', 'budgets.category_id', 'categories.name', 'categories.color', 'categories.icon')
            ->get();

        return $rows->map(function (object $row): array {
            $amount = BigDecimal::of((string) $row->amount);
            $spent = BigDecimal::of((string) $row->spent);
            $remaining = $amount->minus($spent);
            $percent = $amount->isZero()
                ? 0.0
                : $spent->multipliedBy(100)->dividedBy($amount, 1, RoundingMode::HALF_UP)->toFloat();

            return [
                'id' => (int) $row->id,
                'category_id' => (int) $row->category_id,
                'category_name' => (string) $row->category_name,
                'category_color' => $row->category_color !== null ? (string) $row->category_color : null,
                'category_icon' => $row->category_icon !== null ? (string) $row->category_icon : null,
                'amount' => (string) $amount->toScale(2),
                'spent' => (string) $spent->toScale(2),
                'remaining' => (string) $remaining->toScale(2),
                'percent' => $percent,
                'status' => $percent >= 100 ? 'danger' : ($percent >= 80 ? 'warning' : 'ok'),
            ];
        })->sortByDesc('percent')->values()->all();
    }

    /** @return list<array<string, mixed>> */
    public function top(int $userId, string $month, int $limit): array
    {
        return array_slice($this->handle($userId, $month), 0, $limit);
    }
}
