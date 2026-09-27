<?php

namespace App\Models;

use Database\Factories\MissionEnrollmentFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $student_id
 * @property bool $active
 * @property Carbon $activated_at
 * @property Carbon|null $deactivated_at
 * @property Carbon|null $activity_started_at
 * @property Carbon|null $completed_at
 */
#[Fillable(['assignment_id', 'student_id', 'active', 'activated_at', 'deactivated_at', 'activity_started_at', 'completed_at'])]
class MissionEnrollment extends Model
{
    /** @use HasFactory<MissionEnrollmentFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'active' => 'boolean',
            'activated_at' => 'datetime',
            'deactivated_at' => 'datetime',
            'activity_started_at' => 'datetime',
            'completed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<MissionAssignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(MissionAssignment::class, 'assignment_id');
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return HasMany<NodeProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(NodeProgress::class, 'enrollment_id');
    }
}
