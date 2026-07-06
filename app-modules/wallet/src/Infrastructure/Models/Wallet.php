<?php

declare(strict_types=1);

namespace Modules\Wallet\Infrastructure\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Modules\Shared\Concerns\OwnedByUser;
use Modules\Wallet\Database\Factories\WalletFactory;
use Modules\Wallet\Domain\Enums\WalletType;

/**
 * @property int $id
 * @property int $user_id
 * @property string $name
 * @property WalletType $type
 * @property string $initial_balance
 * @property string $current_balance
 * @property string|null $color
 * @property string|null $icon
 * @property bool $is_archived
 */
class Wallet extends Model
{
    /** @use HasFactory<WalletFactory> */
    use HasFactory, OwnedByUser, SoftDeletes;

    /** @var list<string> */
    protected $fillable = [
        'user_id',
        'name',
        'type',
        'initial_balance',
        'current_balance',
        'color',
        'icon',
        'is_archived',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'type' => WalletType::class,
            'initial_balance' => 'decimal:2',
            'current_balance' => 'decimal:2',
            'is_archived' => 'boolean',
        ];
    }

    protected static function newFactory(): WalletFactory
    {
        return WalletFactory::new();
    }
}
