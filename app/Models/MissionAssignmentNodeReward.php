<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $node_id
 * @property int $experience_reward
 * @property int $coin_reward
 */
#[Fillable(['node_id', 'experience_reward', 'coin_reward'])]
class MissionAssignmentNodeReward extends Model
{
    protected function casts(): array
    {
        return [
            'experience_reward' => 'integer',
            'coin_reward' => 'integer',
        ];
    }

    /** @return BelongsTo<MissionAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(MissionAssignment::class, 'assignment_id');
    }

    /** @return BelongsTo<MissionNode, $this> */
    public function node(): BelongsTo
    {
        return $this->belongsTo(MissionNode::class, 'node_id');
    }
}
