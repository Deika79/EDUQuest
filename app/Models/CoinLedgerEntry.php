<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $student_id
 * @property int $amount
 * @property string $reason
 * @property int|null $reward_grant_id
 */
#[Fillable(['amount', 'reason'])]
class CoinLedgerEntry extends Model
{
    public const REASON_ACTIVITY_COMPLETION = 'activity_completion';

    protected function casts(): array
    {
        return ['amount' => 'integer'];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<StudentRewardGrant, $this> */
    public function rewardGrant(): BelongsTo
    {
        return $this->belongsTo(StudentRewardGrant::class, 'reward_grant_id');
    }
}
