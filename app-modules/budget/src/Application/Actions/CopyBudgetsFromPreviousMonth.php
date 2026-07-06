<?php

declare(strict_types=1);

namespace Modules\Budget\Application\Actions;

use Carbon\CarbonImmutable;
use Modules\Budget\Infrastructure\Models\Budget;

class CopyBudgetsFromPreviousMonth
{
    /**
     * AC-14.4: salin semua anggaran bulan sebelumnya ke bulan berjalan;
     * yang sudah ada tidak ditimpa.
     */
    public function handle(int $userId, string $month): int
    {
        $target = CarbonImmutable::parse($month)->startOfMonth();
        $source = $target->subMonthNoOverflow();

        $sourceBudgets = Budget::query()
            ->where('user_id', $userId)
            ->where('month', $source->toDateString())
            ->get();

        $copied = 0;

        foreach ($sourceBudgets as $budget) {
            $created = Budget::firstOrCreate(
                [
                    'user_id' => $userId,
                    'category_id' => $budget->category_id,
                    'month' => $target->toDateString(),
                ],
                ['amount' => $budget->amount],
            );

            if ($created->wasRecentlyCreated) {
                $copied++;
            }
        }

        return $copied;
    }
}
