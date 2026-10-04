<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property string $sku
 * @property string $name
 * @property string $character_key
 * @property string $collection
 * @property string $asset_path
 * @property int $coin_price
 * @property int $minimum_level
 * @property bool $starter
 * @property bool $active
 * @property int $sort_order
 */
#[Fillable([
    'sku',
    'name',
    'character_key',
    'collection',
    'asset_path',
    'coin_price',
    'minimum_level',
    'starter',
    'active',
    'sort_order',
])]
class CosmeticItem extends Model
{
    protected function casts(): array
    {
        return [
            'coin_price' => 'integer',
            'minimum_level' => 'integer',
            'starter' => 'boolean',
            'active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /** @return HasMany<StudentCosmeticItem, $this> */
    public function owners(): HasMany
    {
        return $this->hasMany(StudentCosmeticItem::class);
    }
}
