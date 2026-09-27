<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $enrollment_id
 * @property int $node_id
 * @property string $score
 * @property int $correct_answers
 * @property int $total_questions
 * @property bool $passed
 * @property Carbon $submitted_at
 */
#[Fillable(['enrollment_id', 'node_id', 'score', 'correct_answers', 'total_questions', 'passed', 'submitted_at'])]
class QuizAttempt extends Model
{
    protected function casts(): array
    {
        return [
            'score' => 'decimal:6',
            'correct_answers' => 'integer',
            'total_questions' => 'integer',
            'passed' => 'boolean',
            'submitted_at' => 'datetime',
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

    /** @return HasMany<QuizAnswer, $this> */
    public function answers(): HasMany
    {
        return $this->hasMany(QuizAnswer::class, 'attempt_id');
    }
}
