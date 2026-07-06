<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Queries;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;
use Modules\Ledger\Infrastructure\Models\Transaction;
use Modules\Wallet\Application\Queries\WalletOptionsQuery;

/**
 * Daftar transaksi dengan pencarian & filter kombinasi AND (US-17),
 * pagination server-side 25/halaman (04-NFR.md P-3).
 */
class TransactionIndexQuery
{
    public const PER_PAGE = 25;

    public function __construct(private readonly WalletOptionsQuery $walletOptions)
    {
    }

    /** @return array<string, mixed> */
    public function handle(Request $request): array
    {
        $userId = (int) $request->user()->id;
        $filters = $this->filters($request);

        $base = $this->applyFilters(Transaction::query()->with('category'), $filters);

        $paginator = (clone $base)
            ->orderByDesc('occurred_on')
            ->orderByDesc('id')
            ->paginate(self::PER_PAGE)
            ->withQueryString();

        $walletNames = collect($this->walletOptions->all($userId))->keyBy('id');

        $items = collect($paginator->items())->map(function (Transaction $transaction) use ($walletNames) {
            $wallet = $walletNames->get($transaction->wallet_id);
            $destination = $transaction->destination_wallet_id !== null
                ? $walletNames->get($transaction->destination_wallet_id)
                : null;

            return [
                'id' => $transaction->id,
                'type' => $transaction->type->value,
                'amount' => $transaction->amount,
                'description' => $transaction->description,
                'occurred_on' => $transaction->occurred_on->toDateString(),
                'category' => $transaction->category === null ? null : [
                    'id' => $transaction->category->id,
                    'name' => $transaction->category->name,
                    'color' => $transaction->category->color,
                    'icon' => $transaction->category->icon,
                ],
                'wallet' => $wallet === null ? null : ['id' => $wallet['id'], 'name' => $wallet['name'], 'color' => $wallet['color']],
                'destination_wallet' => $destination === null ? null : ['id' => $destination['id'], 'name' => $destination['name'], 'color' => $destination['color']],
                'wallet_id' => $transaction->wallet_id,
                'destination_wallet_id' => $transaction->destination_wallet_id,
                'category_id' => $transaction->category_id,
                'is_recurring' => $transaction->recurring_transaction_id !== null,
            ];
        });

        $summary = null;
        if ($this->hasActiveFilters($filters)) {
            $netTotal = (clone $base)->selectRaw(
                "COALESCE(SUM(CASE type WHEN 'income' THEN amount WHEN 'expense' THEN -amount ELSE 0 END), 0) AS net_total"
            )->value('net_total');

            $summary = [
                'count' => $paginator->total(),
                'net_total' => (string) $netTotal,
            ];
        }

        return [
            'transactions' => $items,
            'pagination' => [
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'next_page_url' => $paginator->nextPageUrl(),
            ],
            'filters' => $filters,
            'summary' => $summary,
        ];
    }

    /** @return array<string, mixed> */
    public function filters(Request $request): array
    {
        return [
            'q' => (string) $request->query('q', ''),
            'start' => $request->query('start'),
            'end' => $request->query('end'),
            'categories' => array_values(array_filter(array_map('intval', (array) $request->query('categories', [])))),
            'wallet' => $request->query('wallet') !== null ? (int) $request->query('wallet') : null,
            'type' => in_array($request->query('type'), ['income', 'expense', 'transfer'], true) ? $request->query('type') : null,
            'min' => $request->query('min'),
            'max' => $request->query('max'),
        ];
    }

    /**
     * @param  Builder<Transaction>  $query
     * @param  array<string, mixed>  $filters
     * @return Builder<Transaction>
     */
    public function applyFilters(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['q'] !== '', fn (Builder $q) => $q->where('description', 'like', '%'.addcslashes($filters['q'], '%_\\').'%'))
            ->when($filters['start'] !== null, fn (Builder $q) => $q->whereDate('occurred_on', '>=', $filters['start']))
            ->when($filters['end'] !== null, fn (Builder $q) => $q->whereDate('occurred_on', '<=', $filters['end']))
            ->when($filters['categories'] !== [], fn (Builder $q) => $q->whereIn('category_id', $filters['categories']))
            ->when($filters['wallet'] !== null, fn (Builder $q) => $q->where(fn (Builder $inner) => $inner
                ->where('wallet_id', $filters['wallet'])
                ->orWhere('destination_wallet_id', $filters['wallet'])))
            ->when($filters['type'] !== null, fn (Builder $q) => $q->where('type', $filters['type']))
            ->when($filters['min'] !== null, fn (Builder $q) => $q->where('amount', '>=', $filters['min']))
            ->when($filters['max'] !== null, fn (Builder $q) => $q->where('amount', '<=', $filters['max']));
    }

    /** @param  array<string, mixed>  $filters */
    public function hasActiveFilters(array $filters): bool
    {
        return $filters['q'] !== ''
            || $filters['start'] !== null
            || $filters['end'] !== null
            || $filters['categories'] !== []
            || $filters['wallet'] !== null
            || $filters['type'] !== null
            || $filters['min'] !== null
            || $filters['max'] !== null;
    }
}
