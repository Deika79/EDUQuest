<?php

namespace App\Services;

use App\Enums\MissionNodeType;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Support\VideoReference;

class MissionReadiness
{
    /** @return array{ready: bool, errors: list<string>} */
    public function check(Mission $mission): array
    {
        $mission->loadMissing(['nodes.questions.options', 'nodes.flashcards', 'aiGeneration']);
        $errors = [];

        if ($mission->aiGeneration !== null && $mission->aiGeneration->reviewed_at === null) {
            $errors[] = 'Confirm the human review of this AI-assisted draft.';
        }

        foreach ([$mission->title, $mission->description, $mission->subject, $mission->level] as $value) {
            if (blank($value)) {
                $errors[] = 'Complete all mission details.';
                break;
            }
        }

        if ($mission->nodes->isEmpty()) {
            $errors[] = 'Add at least one node.';
        }

        if ($mission->nodes->sum('coin_reward') > 20) {
            $errors[] = 'The mission can award at most 20 coins in total.';
        }

        foreach ($mission->nodes as $node) {
            $prefix = "Node {$node->position} ({$node->title})";

            if ($node->review_required) {
                $errors[] = "{$prefix}: review the AI suggestion before publishing.";
            }

            if (blank($node->title)) {
                $errors[] = "Node {$node->position}: a title is required.";
            }

            if ($node->coin_reward < 0 || $node->coin_reward > 3) {
                $errors[] = "{$prefix}: the coin reward must be between 0 and 3.";
            }

            switch ($node->type) {
                case MissionNodeType::Explanation:
                    $this->checkExplanation($node, $prefix, $errors);
                    break;
                case MissionNodeType::Video:
                    $this->checkVideo($node, $prefix, $errors);
                    break;
                case MissionNodeType::Quiz:
                    $this->checkQuiz($node, $prefix, $errors);
                    break;
                case MissionNodeType::Flashcards:
                    $this->checkFlashcards($node, $prefix, $errors);
                    break;
            }
        }

        return ['ready' => $errors === [], 'errors' => $errors];
    }

    /** @param list<string> $errors */
    private function checkExplanation(MissionNode $node, string $prefix, array &$errors): void
    {
        if (blank($node->body)) {
            $errors[] = "{$prefix}: explanation text is required.";
        }
    }

    /** @param list<string> $errors */
    private function checkVideo(MissionNode $node, string $prefix, array &$errors): void
    {
        if ($node->video_provider === null
            || blank($node->video_id)
            || VideoReference::extractId($node->video_provider, (string) $node->video_id) === null) {
            $errors[] = "{$prefix}: a valid video provider and identifier are required.";
        }
    }

    /** @param list<string> $errors */
    private function checkQuiz(MissionNode $node, string $prefix, array &$errors): void
    {
        if ($node->pass_threshold === null || $node->pass_threshold < 1 || $node->pass_threshold > 100) {
            $errors[] = "{$prefix}: the pass threshold must be between 1 and 100.";
        }

        if ($node->questions->count() < 1 || $node->questions->count() > 10) {
            $errors[] = "{$prefix}: add between 1 and 10 questions.";
        }

        foreach ($node->questions as $question) {
            if (blank($question->statement) || blank($question->explanation)) {
                $errors[] = "{$prefix}: every question needs a statement and feedback explanation.";
            }

            if ($question->options->count() < 2 || $question->options->count() > 4) {
                $errors[] = "{$prefix}: each question needs between 2 and 4 options.";
            }

            if ($question->options->contains(fn ($option) => blank($option->text))) {
                $errors[] = "{$prefix}: every quiz option needs text.";
            }

            if ($question->options->where('is_correct', true)->count() !== 1) {
                $errors[] = "{$prefix}: each question needs exactly one correct option.";
            }
        }
    }

    /** @param list<string> $errors */
    private function checkFlashcards(MissionNode $node, string $prefix, array &$errors): void
    {
        if ($node->flashcards->isEmpty() || $node->flashcards->count() > 10) {
            $errors[] = "{$prefix}: add between 1 and 10 complete flashcards.";
        }

        foreach ($node->flashcards as $flashcard) {
            if (blank($flashcard->front) || blank($flashcard->back)) {
                $errors[] = "{$prefix}: every flashcard needs a front and a back.";
                break;
            }
        }
    }
}
