<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ledger\Database\Factories\TransactionFactory;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Shared\Concerns\OwnedByUser;

/**
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property int|null $destination_wallet_id
 * @property int|null $category_id
 * @property int|null $recurring_transaction_id
 * @property TransactionType $type
 * @property string $amount
 * @property string|null $description
 * @property \Carbon\CarbonImmutable $occurred_on
 */
class Transaction extends Model
{
    /** @use HasFactory<TransactionFactory> */
    use HasFactory, OwnedByUser, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'destination_wallet_id',
        'category_id',
        'recurring_transaction_id',
        'type',
        'amount',
        'description',
        'occurred_on',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'occurred_on' => 'immutable_date',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    /** @return HasMany<Attachment, $this> */
    public function attachments(): HasMany
    {
        return $this->hasMany(Attachment::class);
    }

    protected static function newFactory(): TransactionFactory
    {
        return TransactionFactory::new();
    }
}
