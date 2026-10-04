<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $student_id
 * @property int $node_id
 * @property int $first_progress_id
 * @property int $experience_awarded
 * @property int $coins_awarded
 * @property Carbon $awarded_at
 */
#[Fillable(['experience_awarded', 'coins_awarded', 'awarded_at'])]
class StudentRewardGrant extends Model
{
    protected function casts(): array
    {
        return [
            'experience_awarded' => 'integer',
            'coins_awarded' => 'integer',
            'awarded_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<MissionNode, $this> */
    public function node(): BelongsTo
    {
        return $this->belongsTo(MissionNode::class, 'node_id');
    }

    /** @return BelongsTo<NodeProgress, $this> */
    public function firstProgress(): BelongsTo
    {
        return $this->belongsTo(NodeProgress::class, 'first_progress_id');
    }

    /** @return HasOne<CoinLedgerEntry, $this> */
    public function coinLedgerEntry(): HasOne
    {
        return $this->hasOne(CoinLedgerEntry::class, 'reward_grant_id');
    }
}
