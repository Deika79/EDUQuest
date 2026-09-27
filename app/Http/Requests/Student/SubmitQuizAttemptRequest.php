<?php

namespace App\Http\Requests\Student;

use App\Enums\MissionNodeType;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Services\StudentMissionAccess;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class SubmitQuizAttemptRequest extends FormRequest
{
    public function authorize(): bool
    {
        $enrollment = $this->route('enrollment');
        $node = $this->route('node');

        if (! $enrollment instanceof MissionEnrollment || ! $node instanceof MissionNode) {
            return false;
        }

        app(StudentMissionAccess::class)->assertNode($this->user(), $enrollment, $node);

        return $node->type === MissionNodeType::Quiz;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $node = $this->route('node');
        $questionCount = $node instanceof MissionNode ? $node->questions()->count() : 0;

        return [
            'answers' => ['required', 'array', 'size:'.$questionCount],
            'answers.*' => ['required', 'array:question_id,option_id'],
            'answers.*.question_id' => ['required', 'integer', 'distinct'],
            'answers.*.option_id' => ['required', 'integer'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            $node = $this->route('node');
            $answers = $this->input('answers', []);

            if (! $node instanceof MissionNode || ! is_array($answers)) {
                return;
            }

            $questions = $node->questions()->with('options:id,question_id')->get(['id', 'node_id']);
            $questionLookup = $questions->keyBy('id');
            $submittedQuestionIds = collect($answers)
                ->pluck('question_id')
                ->map(fn ($id) => (int) $id)
                ->sort()
                ->values()
                ->all();
            $nodeQuestionIds = $questions->pluck('id')->sort()->values()->all();

            if ($submittedQuestionIds !== $nodeQuestionIds) {
                $validator->errors()->add('answers', 'Answer every question from this questionnaire exactly once.');

                return;
            }

            foreach ($answers as $index => $answer) {
                if (! is_array($answer) || ! isset($answer['question_id'], $answer['option_id'])) {
                    continue;
                }

                $question = $questionLookup->get((int) $answer['question_id']);
                $optionBelongsToQuestion = $question !== null
                    && $question->options->contains('id', (int) $answer['option_id']);

                if (! $optionBelongsToQuestion) {
                    $validator->errors()->add(
                        "answers.{$index}.option_id",
                        'The selected option does not belong to this question.',
                    );
                }
            }
        }];
    }
}
