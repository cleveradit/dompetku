<?php

declare(strict_types=1);

namespace Modules\Budget\Infrastructure\Models;

use Carbon\CarbonImmutable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Modules\Budget\Database\Factories\BudgetFactory;
use Modules\Shared\Concerns\OwnedByUser;

/**
 * @property int $id
 * @property int $user_id
 * @property int $category_id
 * @property CarbonImmutable $month
 * @property string $amount
 */
class Budget extends Model
{
    /** @use HasFactory<BudgetFactory> */
    use HasFactory, OwnedByUser;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'category_id',
        'month',
        'amount',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'month' => 'immutable_date',
            'amount' => 'decimal:2',
        ];
    }

    protected static function newFactory(): BudgetFactory
    {
        return BudgetFactory::new();
    }
}
