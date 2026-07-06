<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Exceptions;

use RuntimeException;

/** AC-09.4: tipe kategori tidak boleh berubah setelah dipakai transaksi. */
class CategoryTypeLocked extends RuntimeException
{
    public static function make(): self
    {
        return new self('Tipe kategori tidak bisa diubah karena sudah dipakai transaksi. Nama, warna, dan ikon tetap boleh diubah.');
    }
}
