<?php

declare(strict_types=1);

namespace Modules\Ledger\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Ledger\Database\Factories\CategoryFactory;
use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Shared\Concerns\OwnedByUser;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property CategoryType $type
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_default
 */
class Category extends Model
{
    /** @use HasFactory<CategoryFactory> */
    use HasFactory, OwnedByUser, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'color',
        'icon',
        'is_default',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => CategoryType::class,
            'is_default' => 'boolean',
        ];
    }

    protected static function newFactory(): CategoryFactory
    {
        return CategoryFactory::new();
    }
}
