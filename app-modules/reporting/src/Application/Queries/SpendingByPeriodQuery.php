<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Queries;

use Illuminate\Database\Query\Builder;
use Illuminate\Support\Facades\DB;
use Modules\Shared\Enums\ReportInterval;
use Modules\Shared\ValueObjects\DatePeriod;

/**
 * Total income/expense/net + tren pengeluaran per hari/bulan untuk satu
 * periode (US-10). Agregasi murni SQL (04-NFR.md P-2); transfer tidak pernah
 * dihitung income/expense (AC-08.3); soft-deleted tidak ikut (AC-10.9).
 */
class SpendingByPeriodQuery
{
    /** @return array{income: string, expense: string, net: string} */
    public function totals(int $userId, DatePeriod $period, ?int $walletId = null): array
    {
        $row = $this->base($userId, $period, $walletId)
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense,
                COALESCE(SUM(CASE type WHEN 'income' THEN amount WHEN 'expense' THEN -amount ELSE 0 END), 0) AS net
            SQL)
            ->first();

        return [
            'income' => (string) $row->income,
            'expense' => (string) $row->expense,
            'net' => (string) $row->net,
        ];
    }

    /**
     * Tren pengeluaran: per hari (harian/mingguan/bulanan/custom) atau per
     * bulan (tahunan) — AC-10.6.
     *
     * @return list<array{key: string, expense: string, income: string}>
     */
    public function trend(int $userId, DatePeriod $period, ?int $walletId = null): array
    {
        $byMonth = $period->interval === ReportInterval::Yearly;
        $bucketExpr = $byMonth ? "DATE_FORMAT(occurred_on, '%Y-%m')" : 'DATE(occurred_on)';

        $rows = $this->base($userId, $period, $walletId)
            ->selectRaw(<<<SQL
                {$bucketExpr} AS bucket,
                COALESCE(SUM(CASE WHEN type = 'expense' THEN amount ELSE 0 END), 0) AS expense,
                COALESCE(SUM(CASE WHEN type = 'income' THEN amount ELSE 0 END), 0) AS income
            SQL)
            ->groupBy('bucket')
            ->orderBy('bucket')
            ->get()
            ->keyBy('bucket');

        // Isi bucket kosong agar sumbu grafik kontinu.
        $series = [];
        $cursor = $period->start;
        while ($cursor->lessThanOrEqualTo($period->end)) {
            $key = $byMonth ? $cursor->format('Y-m') : $cursor->toDateString();
            $row = $rows->get($key);
            $series[] = [
                'key' => $key,
                'expense' => $row !== null ? (string) $row->expense : '0.00',
                'income' => $row !== null ? (string) $row->income : '0.00',
            ];
            $cursor = $byMonth ? $cursor->addMonth() : $cursor->addDay();
        }

        return $series;
    }

    private function base(int $userId, DatePeriod $period, ?int $walletId): Builder
    {
        return DB::table('transactions')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereBetween('occurred_on', [$period->start->toDateString(), $period->end->toDateString()])
            ->when($walletId !== null, fn (Builder $query) => $query->where(fn (Builder $inner) => $inner
                ->where('wallet_id', $walletId)
                ->orWhere('destination_wallet_id', $walletId)));
    }
}
