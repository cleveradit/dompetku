<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Queries;

use Illuminate\Support\Facades\DB;
use Modules\Shared\ValueObjects\DatePeriod;
use Modules\Wallet\Application\Queries\WalletOptionsQuery;

/**
 * Ringkasan beranda (US-11): total saldo non-arsip (dari cache,
 * 04-NFR.md P-5), kartu dompet, income/expense bulan berjalan, dan
 * 10 transaksi terakhir.
 */
class DashboardSummaryQuery
{
    public function __construct(
        private readonly WalletOptionsQuery $walletOptions,
        private readonly SpendingByPeriodQuery $spendingByPeriod,
        private readonly BudgetProgressQuery $budgetProgress,
    ) {
    }

    /** @return array<string, mixed> */
    public function handle(int $userId): array
    {
        $totalBalance = (string) DB::table('wallets')
            ->where('user_id', $userId)
            ->whereNull('deleted_at')
            ->where('is_archived', false)
            ->selectRaw('COALESCE(SUM(current_balance), 0) AS total')
            ->value('total');

        $month = DatePeriod::monthly(now('Asia/Jakarta')->toImmutable());
        $totals = $this->spendingByPeriod->totals($userId, $month);

        $recent = DB::table('transactions')
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->where('transactions.user_id', $userId)
            ->whereNull('transactions.deleted_at')
            ->orderByDesc('transactions.occurred_on')
            ->orderByDesc('transactions.id')
            ->limit(10)
            ->get([
                'transactions.id',
                'transactions.type',
                'transactions.amount',
                'transactions.description',
                'transactions.occurred_on',
                'transactions.wallet_id',
                'transactions.destination_wallet_id',
                'transactions.category_id',
                'transactions.recurring_transaction_id',
                'categories.name as category_name',
                'categories.color as category_color',
                'categories.icon as category_icon',
            ]);

        $walletMap = collect($this->walletOptions->all($userId))->keyBy('id');

        $recentTransactions = $recent->map(function (object $row) use ($walletMap): array {
            $wallet = $walletMap->get((int) $row->wallet_id);
            $destination = $row->destination_wallet_id !== null ? $walletMap->get((int) $row->destination_wallet_id) : null;

            return [
                'id' => (int) $row->id,
                'type' => (string) $row->type,
                'amount' => (string) $row->amount,
                'description' => $row->description !== null ? (string) $row->description : null,
                'occurred_on' => (string) $row->occurred_on,
                'category' => $row->category_id === null ? null : [
                    'id' => (int) $row->category_id,
                    'name' => (string) $row->category_name,
                    'color' => $row->category_color !== null ? (string) $row->category_color : null,
                    'icon' => $row->category_icon !== null ? (string) $row->category_icon : null,
                ],
                'wallet' => $wallet === null ? null : ['id' => $wallet['id'], 'name' => $wallet['name'], 'color' => $wallet['color']],
                'destination_wallet' => $destination === null ? null : ['id' => $destination['id'], 'name' => $destination['name'], 'color' => $destination['color']],
                'is_recurring' => $row->recurring_transaction_id !== null,
            ];
        })->values()->all();

        return [
            'total_balance' => $totalBalance,
            'wallets' => array_values(array_filter($this->walletOptions->all($userId), fn (array $wallet) => ! $wallet['is_archived'])),
            'month' => [
                'income' => $totals['income'],
                'expense' => $totals['expense'],
                'net' => $totals['net'],
                'label' => $month->start->locale('id')->translatedFormat('F Y'),
            ],
            'recent_transactions' => $recentTransactions,
            'top_budgets' => $this->budgetProgress->top($userId, $month->start->toDateString(), 3),
        ];
    }
}
