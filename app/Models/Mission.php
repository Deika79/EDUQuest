<?php

namespace App\Models;

use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use Database\Factories\MissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $teacher_id
 * @property string $title
 * @property string $description
 * @property string $subject
 * @property string $level
 * @property MissionStatus $status
 * @property MissionSource $source
 */
#[Fillable(['title', 'description', 'subject', 'level'])]
class Mission extends Model
{
    /** @use HasFactory<MissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MissionStatus::class,
            'source' => MissionSource::class,
            'published_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return HasMany<MissionNode, $this> */
    public function nodes(): HasMany
    {
        return $this->hasMany(MissionNode::class)->orderBy('position');
    }

    /** @return HasMany<MissionAssignment, $this> */
    public function assignments(): HasMany
    {
        return $this->hasMany(MissionAssignment::class);
    }
}
