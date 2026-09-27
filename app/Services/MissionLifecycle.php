<?php

namespace App\Services;

use App\Enums\MissionStatus;
use App\Models\Mission;
use App\Models\MissionNode;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MissionLifecycle
{
    public function __construct(private readonly MissionReadiness $readiness) {}

    public function publish(Mission $mission): Mission
    {
        return DB::transaction(function () use ($mission): Mission {
            $lockedMission = Mission::query()->lockForUpdate()->findOrFail($mission->id);

            if ($lockedMission->status !== MissionStatus::Draft) {
                throw ValidationException::withMessages(['mission' => 'Only a draft can be published.']);
            }

            $result = $this->readiness->check($lockedMission);

            if (! $result['ready']) {
                throw ValidationException::withMessages(['mission' => $result['errors']]);
            }

            $lockedMission->forceFill([
                'status' => MissionStatus::Published,
                'published_at' => now(),
            ])->save();

            return $lockedMission;
        });
    }

    public function duplicate(Mission $mission): Mission
    {
        return DB::transaction(function () use ($mission): Mission {
            $source = Mission::query()
                ->with(['nodes.questions.options', 'nodes.flashcards'])
                ->lockForUpdate()
                ->findOrFail($mission->id);

            if ($source->status !== MissionStatus::Published) {
                throw ValidationException::withMessages([
                    'mission' => 'Only a published mission can be duplicated.',
                ]);
            }

            $copy = $source->replicate(['status', 'published_at']);
            $copy->title = $source->title.' (copy)';
            $copy->status = MissionStatus::Draft;
            $copy->published_at = null;
            $copy->save();

            foreach ($source->nodes as $sourceNode) {
                $node = $sourceNode->replicate();
                $node->mission()->associate($copy);
                $node->save();
                $this->copyNodeContent($sourceNode, $node);
            }

            return $copy;
        });
    }

    public function archive(Mission $mission): Mission
    {
        return DB::transaction(function () use ($mission): Mission {
            $lockedMission = Mission::query()->lockForUpdate()->findOrFail($mission->id);

            if ($lockedMission->status === MissionStatus::Archived) {
                throw ValidationException::withMessages(['mission' => 'This mission is already archived.']);
            }

            $lockedMission->forceFill(['status' => MissionStatus::Archived])->save();

            return $lockedMission;
        });
    }

    private function copyNodeContent(MissionNode $source, MissionNode $copy): void
    {
        foreach ($source->questions as $sourceQuestion) {
            $question = $sourceQuestion->replicate();
            $question->node()->associate($copy);
            $question->save();

            foreach ($sourceQuestion->options as $sourceOption) {
                $option = $sourceOption->replicate();
                $option->question()->associate($question);
                $option->save();
            }
        }

        foreach ($source->flashcards as $sourceFlashcard) {
            $flashcard = $sourceFlashcard->replicate();
            $flashcard->node()->associate($copy);
            $flashcard->save();
        }
    }
}
