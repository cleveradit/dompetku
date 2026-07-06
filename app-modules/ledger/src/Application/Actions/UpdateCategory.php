<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Illuminate\Support\Facades\DB;
use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Ledger\Domain\Exceptions\CategoryTypeLocked;
use Modules\Ledger\Infrastructure\Models\Category;

class UpdateCategory
{
    /**
     * AC-09.4: tipe immutable setelah dipakai transaksi; nama/warna/ikon
     * tetap boleh diubah.
     *
     * @throws CategoryTypeLocked
     */
    public function handle(Category $category, string $name, CategoryType $type, ?string $color, ?string $icon): Category
    {
        if ($type !== $category->type && $this->isUsedByTransactions($category)) {
            throw CategoryTypeLocked::make();
        }

        $category->fill([
            'name' => $name,
            'type' => $type,
            'color' => $color,
            'icon' => $icon,
        ]);

        $category->save();

        return $category;
    }

    private function isUsedByTransactions(Category $category): bool
    {
        return DB::table('transactions')->where('category_id', $category->id)->exists();
    }
}
