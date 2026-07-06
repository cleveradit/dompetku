<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Modules\Ledger\Database\Factories\RecurringTransactionFactory;
use Modules\Ledger\Domain\Enums\RecurringFrequency;
use Modules\Ledger\Domain\Enums\TransactionType;
use Modules\Shared\Concerns\OwnedByUser;

/**
 * @property int $id
 * @property int $user_id
 * @property int $wallet_id
 * @property int|null $destination_wallet_id
 * @property int|null $category_id
 * @property TransactionType $type
 * @property string $amount
 * @property string|null $description
 * @property RecurringFrequency $frequency
 * @property int $interval
 * @property CarbonImmutable $next_run_on
 * @property CarbonImmutable|null $end_on
 * @property CarbonImmutable|null $last_run_on
 * @property bool $is_active
 */
class RecurringTransaction extends Model
{
    /** @use HasFactory<RecurringTransactionFactory> */
    use HasFactory, OwnedByUser;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'wallet_id',
        'destination_wallet_id',
        'category_id',
        'type',
        'amount',
        'description',
        'frequency',
        'interval',
        'next_run_on',
        'end_on',
        'last_run_on',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => TransactionType::class,
            'amount' => 'decimal:2',
            'frequency' => RecurringFrequency::class,
            'interval' => 'integer',
            'next_run_on' => 'immutable_date',
            'end_on' => 'immutable_date',
            'last_run_on' => 'immutable_date',
            'is_active' => 'boolean',
        ];
    }

    /** @return BelongsTo<Category, $this> */
    public function category(): BelongsTo
    {
        return $this->belongsTo(Category::class)->withTrashed();
    }

    protected static function newFactory(): RecurringTransactionFactory
    {
        return RecurringTransactionFactory::new();
    }
}
