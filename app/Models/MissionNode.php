<?php

namespace App\Models;

use App\Enums\MissionNodeType;
use App\Enums\VideoProvider;
use Database\Factories\MissionNodeFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * @property int $id
 * @property int $mission_id
 * @property int $position
 * @property MissionNodeType $type
 * @property string $title
 * @property string|null $body
 * @property VideoProvider|null $video_provider
 * @property string|null $video_id
 * @property int|null $pass_threshold
 */
#[Fillable(['title', 'type', 'position', 'body', 'video_provider', 'video_id', 'pass_threshold'])]
class MissionNode extends Model
{
    /** @use HasFactory<MissionNodeFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'type' => MissionNodeType::class,
            'video_provider' => VideoProvider::class,
            'position' => 'integer',
            'pass_threshold' => 'integer',
        ];
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }

    /** @return HasMany<QuizQuestion, $this> */
    public function questions(): HasMany
    {
        return $this->hasMany(QuizQuestion::class, 'node_id')->orderBy('position');
    }

    /** @return HasMany<Flashcard, $this> */
    public function flashcards(): HasMany
    {
        return $this->hasMany(Flashcard::class, 'node_id')->orderBy('position');
    }
}
