<?php

namespace App\Models;

use Database\Factories\NodeProgressFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property int $node_id
 * @property Carbon $completed_at
 * @property int $points_awarded
 */
#[Fillable(['enrollment_id', 'node_id', 'completed_at', 'points_awarded'])]
class NodeProgress extends Model
{
    /** @use HasFactory<NodeProgressFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'completed_at' => 'datetime',
            'points_awarded' => 'integer',
        ];
    }

    /** @return BelongsTo<MissionEnrollment, $this> */
    public function enrollment(): BelongsTo
    {
        return $this->belongsTo(MissionEnrollment::class, 'enrollment_id');
    }

    /** @return BelongsTo<MissionNode, $this> */
    public function node(): BelongsTo
    {
        return $this->belongsTo(MissionNode::class, 'node_id');
    }
}
