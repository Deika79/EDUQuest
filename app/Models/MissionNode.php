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
 * @property bool $review_required
 * @property string|null $review_note
 */
#[Fillable([
    'title',
    'type',
    'position',
    'body',
    'video_provider',
    'video_id',
    'pass_threshold',
    'review_required',
    'review_note',
])]
class MissionNode extends Model
{
    /** @use HasFactory<MissionNodeFactory> */
    use HasFactory;

    protected static function booted(): void
    {
        $resetAiReview = function (MissionNode $node): void {
            $node->mission?->aiGeneration()->update(['reviewed_at' => null]);
        };

        static::saved($resetAiReview);
        static::deleted($resetAiReview);
    }

    protected function casts(): array
    {
        return [
            'type' => MissionNodeType::class,
            'video_provider' => VideoProvider::class,
            'position' => 'integer',
            'pass_threshold' => 'integer',
            'review_required' => 'boolean',
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

    /** @return HasMany<NodeProgress, $this> */
    public function progress(): HasMany
    {
        return $this->hasMany(NodeProgress::class, 'node_id');
    }

    /** @return HasMany<QuizAttempt, $this> */
    public function attempts(): HasMany
    {
        return $this->hasMany(QuizAttempt::class, 'node_id');
    }
}
