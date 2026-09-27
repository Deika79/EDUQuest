<?php

namespace App\Models;

use App\Enums\MissionAssignmentStatus;
use Database\Factories\MissionAssignmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $mission_id
 * @property int $classroom_id
 * @property MissionAssignmentStatus $status
 * @property Carbon $assigned_at
 * @property Carbon|null $closed_at
 */
#[Fillable(['status', 'assigned_at', 'closed_at'])]
class MissionAssignment extends Model
{
    /** @use HasFactory<MissionAssignmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MissionAssignmentStatus::class,
            'assigned_at' => 'datetime',
            'closed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /** @return BelongsTo<Classroom, $this> */
    public function classroom(): BelongsTo
    {
        return $this->belongsTo(Classroom::class);
    }

    /** @return HasMany<MissionEnrollment, $this> */
    public function enrollments(): HasMany
    {
        return $this->hasMany(MissionEnrollment::class, 'assignment_id');
    }
}
