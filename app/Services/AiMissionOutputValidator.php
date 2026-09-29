<?php

namespace App\Services;

use App\Enums\MissionNodeType;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Validator as LaravelValidator;

class AiMissionOutputValidator
{
    /**
     * @param  array<string, mixed>  $content
     * @return array<string, mixed>
     *
     * @throws ValidationException
     */
    public function validate(array $content): array
    {
        $validator = Validator::make($content, [
            'title' => ['required', 'string', 'max:160'],
            'description' => ['required', 'string', 'max:5000'],
            'nodes' => ['required', 'array', 'min:4', 'max:8'],
            'nodes.*' => ['array:type,title,body,video_search_terms,pass_threshold,questions,flashcards'],
            'nodes.*.type' => ['required', Rule::enum(MissionNodeType::class)],
            'nodes.*.title' => ['required', 'string', 'max:160'],
            'nodes.*.body' => ['present', 'nullable', 'string', 'max:10000'],
            'nodes.*.video_search_terms' => ['present', 'nullable', 'string', 'max:500'],
            'nodes.*.pass_threshold' => ['present', 'nullable', 'integer', 'between:1,100'],
            'nodes.*.questions' => ['present', 'array', 'max:10'],
            'nodes.*.questions.*' => ['array:statement,explanation,options'],
            'nodes.*.questions.*.statement' => ['required', 'string', 'max:2000'],
            'nodes.*.questions.*.explanation' => ['required', 'string', 'max:3000'],
            'nodes.*.questions.*.options' => ['required', 'array', 'min:2', 'max:4'],
            'nodes.*.questions.*.options.*' => ['array:text,is_correct'],
            'nodes.*.questions.*.options.*.text' => ['required', 'string', 'max:1000'],
            'nodes.*.questions.*.options.*.is_correct' => ['required', 'boolean'],
            'nodes.*.flashcards' => ['present', 'array', 'max:10'],
            'nodes.*.flashcards.*' => ['array:front,back'],
            'nodes.*.flashcards.*.front' => ['required', 'string', 'max:2000'],
            'nodes.*.flashcards.*.back' => ['required', 'string', 'max:3000'],
        ]);

        $validator->after(function (LaravelValidator $validator) use ($content): void {
            if (array_diff(array_keys($content), ['title', 'description', 'nodes']) !== []) {
                $validator->errors()->add('output', 'La respuesta contiene campos no permitidos.');
            }

            foreach (($content['nodes'] ?? []) as $index => $node) {
                if (! is_array($node)) {
                    continue;
                }

                $type = $node['type'] ?? null;
                $questions = is_array($node['questions'] ?? null) ? $node['questions'] : [];
                $flashcards = is_array($node['flashcards'] ?? null) ? $node['flashcards'] : [];

                if ($type === MissionNodeType::Explanation->value && blank($node['body'] ?? null)) {
                    $validator->errors()->add("nodes.{$index}.body", 'La explicacion necesita contenido.');
                }

                if ($type === MissionNodeType::Video->value) {
                    $terms = $node['video_search_terms'] ?? null;

                    if (blank($terms) || filter_var($terms, FILTER_VALIDATE_URL) !== false) {
                        $validator->errors()->add(
                            "nodes.{$index}.video_search_terms",
                            'El video debe contener terminos de busqueda, no una URL.',
                        );
                    }
                }

                if ($type === MissionNodeType::Quiz->value) {
                    if (count($questions) < 1 || count($questions) > 10) {
                        $validator->errors()->add(
                            "nodes.{$index}.questions",
                            'El cuestionario debe contener entre 1 y 10 preguntas.',
                        );
                    }

                    foreach ($questions as $questionIndex => $question) {
                        $options = is_array($question) && is_array($question['options'] ?? null)
                            ? $question['options']
                            : [];
                        $correct = collect($options)->filter(
                            fn (mixed $option): bool => is_array($option) && ($option['is_correct'] ?? null) === true,
                        )->count();

                        if ($correct !== 1) {
                            $validator->errors()->add(
                                "nodes.{$index}.questions.{$questionIndex}.options",
                                'Cada pregunta debe tener exactamente una respuesta correcta.',
                            );
                        }
                    }
                }

                if ($type === MissionNodeType::Flashcards->value
                    && (count($flashcards) < 1 || count($flashcards) > 10)) {
                    $validator->errors()->add(
                        "nodes.{$index}.flashcards",
                        'El bloque debe contener entre 1 y 10 tarjetas.',
                    );
                }
            }
        });

        return $validator->validate();
    }
}
