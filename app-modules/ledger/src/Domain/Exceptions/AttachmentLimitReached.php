<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Exceptions;

use RuntimeException;

/** I-10: maksimal 5 lampiran per transaksi. */
class AttachmentLimitReached extends RuntimeException
{
    public static function make(): self
    {
        return new self('Transaksi ini sudah punya 5 lampiran, jumlah maksimalnya.');
    }
}
