<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $transaction_id
 * @property string $path
 * @property string $original_name
 * @property string $mime_type
 * @property int $size_bytes
 */
class Attachment extends Model
{
    /** @var list<string> */
    protected $fillable = [
        'transaction_id',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
    ];

    /** @return BelongsTo<Transaction, $this> */
    public function transaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class);
    }
}
