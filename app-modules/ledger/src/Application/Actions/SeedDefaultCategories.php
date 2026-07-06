<?php

declare(strict_types=1);

namespace Modules\Ledger\Application\Actions;

use Modules\Ledger\Domain\Enums\CategoryType;
use Modules\Ledger\Infrastructure\Models\Category;

class SeedDefaultCategories
{
    /**
     * Kategori default (02-DATABASE.md 2.3), warna dari palet pecahan rupiah
     * dan ikon whitelist 05-DESIGN.md.
     *
     * @var array<string, list<array{name: string, color: string, icon: string}>>
     */
    private const DEFAULTS = [
        'expense' => [
            ['name' => 'Makan & Minum', 'color' => '#B94A48', 'icon' => 'utensils'],
            ['name' => 'Transportasi', 'color' => '#3E5BAA', 'icon' => 'bus'],
            ['name' => 'Belanja', 'color' => '#6D5BA8', 'icon' => 'shopping-bag'],
            ['name' => 'Tagihan', 'color' => '#8A5A3B', 'icon' => 'receipt'],
            ['name' => 'Kesehatan', 'color' => '#B94A48', 'icon' => 'heart-pulse'],
            ['name' => 'Hiburan', 'color' => '#A08C3B', 'icon' => 'film'],
            ['name' => 'Pendidikan', 'color' => '#3E5BAA', 'icon' => 'graduation-cap'],
            ['name' => 'Lainnya', 'color' => '#6B7280', 'icon' => 'sparkles'],
        ],
        'income' => [
            ['name' => 'Gaji', 'color' => '#2E7D5B', 'icon' => 'banknote'],
            ['name' => 'Bonus', 'color' => '#A08C3B', 'icon' => 'hand-coins'],
            ['name' => 'Hadiah', 'color' => '#6D5BA8', 'icon' => 'gift'],
            ['name' => 'Lainnya', 'color' => '#6B7280', 'icon' => 'sparkles'],
        ],
    ];

    public function handle(int $userId): void
    {
        foreach (self::DEFAULTS as $type => $categories) {
            foreach ($categories as $category) {
                Category::withoutGlobalScopes()->firstOrCreate([
                    'user_id' => $userId,
                    'name' => $category['name'],
                    'type' => CategoryType::from($type),
                ], [
                    'color' => $category['color'],
                    'icon' => $category['icon'],
                    'is_default' => true,
                ]);
            }
        }
    }
}
