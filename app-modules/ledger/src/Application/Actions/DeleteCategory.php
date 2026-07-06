<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Domain\Exceptions\CategoryInUse;
use Modules\Ledger\Infrastructure\Models\Category;

class DeleteCategory
{
    /**
     * I-6: kategori yang dipakai transaksi/recurring/budget ditolak dengan
     * pesan jumlah pemakainya; yang tidak dipakai di-soft-delete (AC-09.2).
     *
     * @throws CategoryInUse
     */
    public function handle(Category $category): void
    {
        $usageCount = $this->usageCount($category);

        if ($usageCount > 0) {
            throw CategoryInUse::make($usageCount);
        }

        $category->delete();
    }

    public function usageCount(Category $category): int
    {
        return DB::table('transactions')->where('category_id', $category->id)->count()
            + DB::table('recurring_transactions')->where('category_id', $category->id)->count()
            + DB::table('budgets')->where('category_id', $category->id)->count();
    }
}
