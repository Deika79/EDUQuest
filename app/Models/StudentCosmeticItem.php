<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property int $cosmetic_item_id
 * @property string $acquisition_type
 * @property Carbon $acquired_at
 */
#[Fillable(['acquisition_type', 'acquired_at'])]
class StudentCosmeticItem extends Model
{
    public const ACQUISITION_STARTER = 'starter';

    public const ACQUISITION_PURCHASE = 'purchase';

    protected function casts(): array
    {
        return ['acquired_at' => 'datetime'];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<CosmeticItem, $this> */
    public function cosmeticItem(): BelongsTo
    {
        return $this->belongsTo(CosmeticItem::class);
    }
}
