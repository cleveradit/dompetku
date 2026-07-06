<?php

declare(strict_types=1);

namespace Modules\Reporting\Application\Exports;

use Illuminate\Contracts\Queue\ShouldQueue;
use Maatwebsite\Excel\Concerns\FromQuery;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithMapping;
use Modules\Ledger\Application\Queries\TransactionIndexQuery;
use Modules\Ledger\Infrastructure\Models\Transaction;

/**
 * US-19: kolom tanggal, tipe, kategori, dompet, dompet tujuan, nominal,
 * catatan; nominal 2 desimal tanpa pembulatan salah (AC-19.3). Berjalan juga
 * di queue tanpa auth — scope user eksplisit.
 */
class TransactionsExport implements FromQuery, ShouldQueue, WithHeadings, WithMapping
{
    use \Maatwebsite\Excel\Concerns\Exportable;

    private const TYPE_LABELS = [
        'income' => 'Pemasukan',
        'expense' => 'Pengeluaran',
        'transfer' => 'Transfer',
    ];

    /** @param  array<string, mixed>  $filters */
    public function __construct(
        private readonly int $userId,
        private readonly array $filters,
    ) {
    }

    public function query()
    {
        $base = Transaction::withoutGlobalScope('ownedByUser')
            ->where('transactions.user_id', $this->userId)
            ->leftJoin('categories', 'categories.id', '=', 'transactions.category_id')
            ->leftJoin('wallets AS source_wallets', 'source_wallets.id', '=', 'transactions.wallet_id')
            ->leftJoin('wallets AS destination_wallets', 'destination_wallets.id', '=', 'transactions.destination_wallet_id')
            ->select([
                'transactions.*',
                'categories.name AS category_name',
                'source_wallets.name AS wallet_name',
                'destination_wallets.name AS destination_wallet_name',
            ])
            ->orderByDesc('transactions.occurred_on')
            ->orderByDesc('transactions.id');

        return app(TransactionIndexQuery::class)->applyFilters($base, $this->filters);
    }

    /** @return list<string> */
    public function headings(): array
    {
        return ['Tanggal', 'Tipe', 'Kategori', 'Dompet', 'Dompet tujuan', 'Nominal', 'Catatan'];
    }

    /**
     * @param  Transaction  $row
     * @return list<string|null>
     */
    public function map($row): array
    {
        return [
            $row->occurred_on->toDateString(),
            self::TYPE_LABELS[$row->type->value] ?? $row->type->value,
            $row->getAttribute('category_name'),
            $row->getAttribute('wallet_name'),
            $row->getAttribute('destination_wallet_name'),
            $row->amount,
            $row->description,
        ];
    }
}
