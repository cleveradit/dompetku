<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Exceptions;

use RuntimeException;

/** I-6: kategori yang dipakai transaksi/recurring/budget tidak boleh dihapus. */
class CategoryInUse extends RuntimeException
{
    public static function make(int $usageCount): self
    {
        return new self("Kategori ini dipakai {$usageCount} data (transaksi, transaksi berulang, atau anggaran) dan tidak bisa dihapus.");
    }
}
