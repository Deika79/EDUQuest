<?php

namespace App\Http\Requests\Student;

use App\Enums\MissionNodeType;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Services\StudentMissionAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class CompleteMissionNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');
        $node = $this->route('node');

        if (! $enrollment instanceof MissionEnrollment || ! $node instanceof MissionNode) {
            return false;
        }

        app(StudentMissionAccess::class)->assertNode($this->user(), $enrollment, $node);

        return true;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $node = $this->route('node');
        $flashcards = $node instanceof MissionNode && $node->type === MissionNodeType::Flashcards;

        return [
            'confirmed' => ['required', 'accepted'],
            'flashcard_ids' => [Rule::excludeIf(! $flashcards), 'required', 'array', 'min:1'],
            'flashcard_ids.*' => [Rule::excludeIf(! $flashcards), 'required', 'integer', 'distinct'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $node = $this->route('node');

            if (! $node instanceof MissionNode || $node->type !== MissionNodeType::Flashcards) {
                return;
            }

            $submitted = $this->input('flashcard_ids', []);

            if (! is_array($submitted)) {
                return;
            }

            $submittedIds = collect($submitted)->map(fn ($id) => (int) $id)->sort()->values()->all();
            $nodeIds = $node->flashcards()->pluck('id')->sort()->values()->all();

            if ($submittedIds !== $nodeIds) {
                $validator->errors()->add(
                    'flashcard_ids',
                    'Review every flashcard from this activity before completing it.',
                );
            }
        }];
    }
}
