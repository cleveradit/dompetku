<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Queries;

use Brick\Math\BigDecimal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Modules\Shared\ValueObjects\DatePeriod;

/**
 * Tren saldo total per hari (US-21, AC-21.1). Dihitung dari initial_balance +
 * mutasi kumulatif transaksi, BUKAN dari current_balance cache, sehingga hasil
 * tetap benar walau cache tidak pernah diperbarui (mis. data seed/factory).
 *
 * Transfer antar dompet diabaikan dalam agregasi mutasi: di aplikasi ini semua
 * transfer terjadi antar dompet milik user yang sama dan tidak soft-deleted,
 * sehingga efek nettonya terhadap SALDO GABUNGAN semua dompet user selalu 0
 * (dompet asal -X, dompet tujuan +X). Tidak perlu menjumlahkan baris transfer.
 */
class BalanceTrendQuery
{
    /** @return list<array{date: string, balance: string}> */
    public function handle(int $userId, DatePeriod $period): array
    {
        $openingBalance = $this->openingBalance($userId, $period);
        $dailyMutations = $this->dailyMutations($userId, $period);

        $series = [];
        $running = $openingBalance;
        $cursor = $period->start;

        while ($cursor->lessThanOrEqualTo($period->end)) {
            $date = $cursor->toDateString();
            $mutation = $dailyMutations->get($date);

            if ($mutation !== null) {
                $running = $running->plus(BigDecimal::of((string) $mutation));
            }

            $series[] = [
                'date' => $date,
                'balance' => (string) $running->toScale(2),
            ];

            $cursor = $cursor->addDay();
        }

        return $series;
    }

    /**
     * Saldo awal gabungan = SUM(initial_balance) semua dompet user (termasuk
     * yang diarsipkan; histori arsip tetap dihitung) + seluruh mutasi
     * transaksi (income/expense) SEBELUM period->start.
     */
    private function openingBalance(int $userId, DatePeriod $period): BigDecimal
    {
        $initialTotal = DB::table('wallets')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->sum('initial_balance');

        $priorMutation = DB::table('transactions')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('occurred_on', '<', $period->start->toDateString())
            ->selectRaw(<<<'SQL'
                COALESCE(SUM(CASE type WHEN 'income' THEN amount WHEN 'expense' THEN -amount ELSE 0 END), 0) AS mutation
            SQL)
            ->value('mutation');

        return BigDecimal::of((string) $initialTotal)->plus(BigDecimal::of((string) $priorMutation));
    }

    /** @return Collection<string, string> keyed by date (Y-m-d) */
    private function dailyMutations(int $userId, DatePeriod $period)
    {
        return DB::table('transactions')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->whereBetween('occurred_on', [$period->start->toDateString(), $period->end->toDateString()])
            ->selectRaw(<<<'SQL'
                DATE(occurred_on) AS bucket,
                COALESCE(SUM(CASE type WHEN 'income' THEN amount WHEN 'expense' THEN -amount ELSE 0 END), 0) AS mutation
            SQL)
            ->groupBy('bucket')
            ->pluck('mutation', 'bucket');
    }
}
