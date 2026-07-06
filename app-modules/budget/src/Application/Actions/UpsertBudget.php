<?php

declare(strict_types=1);

namespace Modules\Budget\Application\Actions;

use Carbon\CarbonImmutable;
use Modules\Budget\Infrastructure\Models\Budget;

class UpsertBudget
{
    /**
     * AC-14.1: satu kategori hanya punya satu anggaran per bulan (upsert);
     * I-8: month dinormalisasi ke tanggal 1.
     */
    public function handle(int $userId, int $categoryId, string $month, string $amount): Budget
    {
        $normalizedMonth = CarbonImmutable::parse($month)->startOfMonth()->toDateString();

        return Budget::updateOrCreate(
            [
                'user_id' => $userId,
                'category_id' => $categoryId,
                'month' => $normalizedMonth,
            ],
            ['amount' => $amount],
        );
    }
}
