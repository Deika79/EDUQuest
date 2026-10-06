<?php

namespace App\Models;

use App\Enums\MissionMapTheme;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use Database\Factories\MissionFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property int $teacher_id
 * @property string $title
 * @property string $description
 * @property string $subject
 * @property string $level
 * @property MissionMapTheme $map_theme
 * @property MissionStatus $status
 * @property MissionSource $source
 */
#[Fillable(['title', 'description', 'subject', 'level', 'map_theme'])]
class Mission extends Model
{
    /** @use HasFactory<MissionFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'status' => MissionStatus::class,
            'source' => MissionSource::class,
            'map_theme' => MissionMapTheme::class,
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

    /** @return HasOne<AiGeneration, $this> */
    public function aiGeneration(): HasOne
    {
        return $this->hasOne(AiGeneration::class);
    }
}
