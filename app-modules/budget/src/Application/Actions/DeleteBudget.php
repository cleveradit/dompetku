<?php

declare(strict_types=1);

namespace Modules\Budget\Application\Actions;

use Modules\Budget\Infrastructure\Models\Budget;

class DeleteBudget
{
    /** AC-14.3: hapus anggaran tanpa mempengaruhi transaksi (tanpa soft delete). */
    public function handle(Budget $budget): void
    {
        $budget->delete();
    }
}
