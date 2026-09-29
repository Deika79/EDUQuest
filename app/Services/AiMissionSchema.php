<?php

namespace App\Services;

class AiMissionSchema
{
    /** @return array<string, mixed> */
    public static function get(): array
    {
        $option = [
            'type' => 'object',
            'properties' => [
                'text' => ['type' => 'string'],
                'is_correct' => ['type' => 'boolean'],
            ],
            'required' => ['text', 'is_correct'],
            'additionalProperties' => false,
        ];

        $question = [
            'type' => 'object',
            'properties' => [
                'statement' => ['type' => 'string'],
                'explanation' => ['type' => 'string'],
                'options' => [
                    'type' => 'array',
                    'items' => $option,
                    'minItems' => 2,
                    'maxItems' => 4,
                ],
            ],
            'required' => ['statement', 'explanation', 'options'],
            'additionalProperties' => false,
        ];

        $flashcard = [
            'type' => 'object',
            'properties' => [
                'front' => ['type' => 'string'],
                'back' => ['type' => 'string'],
            ],
            'required' => ['front', 'back'],
            'additionalProperties' => false,
        ];

        $node = [
            'type' => 'object',
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'enum' => ['explanation', 'video', 'quiz', 'flashcards'],
                ],
                'title' => ['type' => 'string'],
                'body' => ['type' => ['string', 'null']],
                'video_search_terms' => ['type' => ['string', 'null']],
                'pass_threshold' => ['type' => ['integer', 'null']],
                'questions' => [
                    'type' => 'array',
                    'items' => $question,
                    'maxItems' => 10,
                ],
                'flashcards' => [
                    'type' => 'array',
                    'items' => $flashcard,
                    'maxItems' => 10,
                ],
            ],
            'required' => [
                'type',
                'title',
                'body',
                'video_search_terms',
                'pass_threshold',
                'questions',
                'flashcards',
            ],
            'additionalProperties' => false,
        ];

        return [
            'type' => 'object',
            'properties' => [
                'title' => ['type' => 'string'],
                'description' => ['type' => 'string'],
                'nodes' => [
                    'type' => 'array',
                    'items' => $node,
                    'minItems' => 4,
                    'maxItems' => 8,
                ],
            ],
            'required' => ['title', 'description', 'nodes'],
            'additionalProperties' => false,
        ];
    }
}
