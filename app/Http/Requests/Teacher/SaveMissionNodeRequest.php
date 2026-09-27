<?php

namespace App\Http\Requests\Teacher;

use App\Enums\MissionNodeType;
use App\Enums\VideoProvider;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Rules\ValidVideoReference;
use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class SaveMissionNodeRequest extends FormRequest
{
    public function authorize(): bool
    {
        $mission = $this->route('mission');
        $node = $this->route('node');

        if (! $mission instanceof Mission || ! $this->user()->can('update', $mission)) {
            return false;
        }

        return ! $node instanceof MissionNode || $node->mission_id === $mission->id;
    }

    /** @return array<string, ValidationRule|array<mixed>|string> */
    public function rules(): array
    {
        $type = (string) $this->input('type');
        $provider = VideoProvider::tryFrom((string) $this->input('video_provider'));

        return [
            'title' => ['required', 'string', 'max:160'],
            'type' => ['required', Rule::enum(MissionNodeType::class)],
            'body' => [Rule::excludeIf($type !== MissionNodeType::Explanation->value), 'required', 'string', 'max:10000'],
            'video_provider' => [Rule::excludeIf($type !== MissionNodeType::Video->value), 'required', Rule::enum(VideoProvider::class)],
            'video_reference' => [Rule::excludeIf($type !== MissionNodeType::Video->value), 'required', 'string', 'max:500', new ValidVideoReference($provider)],
            'pass_threshold' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'integer', 'between:1,100'],
            'questions' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'array', 'min:1', 'max:10'],
            'questions.*.statement' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'string', 'max:2000'],
            'questions.*.explanation' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'string', 'max:3000'],
            'questions.*.options' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'array', 'min:2', 'max:4'],
            'questions.*.options.*.text' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'string', 'max:1000'],
            'questions.*.options.*.is_correct' => [Rule::excludeIf($type !== MissionNodeType::Quiz->value), 'required', 'boolean'],
            'flashcards' => [Rule::excludeIf($type !== MissionNodeType::Flashcards->value), 'required', 'array', 'min:1', 'max:10'],
            'flashcards.*.front' => [Rule::excludeIf($type !== MissionNodeType::Flashcards->value), 'required', 'string', 'max:2000'],
            'flashcards.*.back' => [Rule::excludeIf($type !== MissionNodeType::Flashcards->value), 'required', 'string', 'max:3000'],
        ];
    }

    /** @return array<callable(Validator): void> */
    public function after(): array
    {
        return [function (Validator $validator): void {
            if ($this->input('type') !== MissionNodeType::Quiz->value || ! is_array($this->input('questions'))) {
                return;
            }

            foreach ($this->input('questions') as $questionIndex => $question) {
                $options = is_array($question) && is_array($question['options'] ?? null)
                    ? $question['options']
                    : [];
                $correct = collect($options)->filter(
                    fn ($option) => is_array($option) && filter_var($option['is_correct'] ?? false, FILTER_VALIDATE_BOOL),
                )->count();

                if ($correct !== 1) {
                    $validator->errors()->add(
                        "questions.{$questionIndex}.options",
                        'Each question must have exactly one correct option.',
                    );
                }
            }
        }];
    }
}
