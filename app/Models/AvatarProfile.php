<?php

namespace App\Models;

use Database\Factories\AvatarProfileFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property string $character_key
 * @property int|null $equipped_cosmetic_item_id
 * @property Carbon $setup_completed_at
 */
#[Fillable([
    'character_key',
    'setup_completed_at',
])]
class AvatarProfile extends Model
{
    /** @use HasFactory<AvatarProfileFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'setup_completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<CosmeticItem, $this> */
    public function equippedAppearance(): BelongsTo
    {
        return $this->belongsTo(CosmeticItem::class, 'equipped_cosmetic_item_id');
    }
}
