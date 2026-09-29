<?php

namespace App\Models;

use App\Enums\AiGenerationStatus;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $teacher_id
 * @property int|null $mission_id
 * @property string $request_token
 * @property AiGenerationStatus $status
 * @property array<string, mixed> $input_summary
 * @property array<string, mixed>|null $output_json
 * @property string|null $error_code
 */
#[Fillable([
    'request_token',
    'status',
    'input_summary',
    'output_json',
    'error_code',
    'provider_response_id',
    'input_tokens',
    'output_tokens',
    'reviewed_at',
])]
class AiGeneration extends Model
{
    protected function casts(): array
    {
        return [
            'status' => AiGenerationStatus::class,
            'input_summary' => 'array',
            'output_json' => 'array',
            'input_tokens' => 'integer',
            'output_tokens' => 'integer',
            'reviewed_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<User, $this> */
    public function teacher(): BelongsTo
    {
        return $this->belongsTo(User::class, 'teacher_id');
    }

    /** @return BelongsTo<Mission, $this> */
    public function mission(): BelongsTo
    {
        return $this->belongsTo(Mission::class);
    }
}
