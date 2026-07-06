<?php

declare(strict_types=1);

namespace Modules\Wallet\Domain\Exceptions;

use RuntimeException;

/**
 * I-7: dompet dengan transaksi (termasuk sebagai tujuan transfer) atau
 * recurring aktif tidak boleh dihapus — tawarkan arsip.
 */
class WalletHasTransactions extends RuntimeException
{
    public static function make(): self
    {
        return new self('Dompet ini punya transaksi atau transaksi berulang. Arsipkan saja supaya historinya tetap aman.');
    }
}
