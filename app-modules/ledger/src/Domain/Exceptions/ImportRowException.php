<?php

declare(strict_types=1);

namespace Modules\Ledger\Domain\Exceptions;

use RuntimeException;

/** US-22: satu baris CSV import tidak valid; pesan sudah siap tampil ke user. */
class ImportRowException extends RuntimeException {}
