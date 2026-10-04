<?php

namespace App\Services;

use App\Enums\MissionNodeType;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\NodeProgress;
use App\Models\User;
use Illuminate\Support\Facades\DB;

class NodeProgressService
{
    public function __construct(
        private readonly StudentMissionAccess $access,
        private readonly StudentRewardService $rewards,
    ) {}

    public function complete(User $student, MissionEnrollment $enrollment, MissionNode $node): NodeProgress
    {
        return $this->completeForTypes($student, $enrollment, $node, [
            MissionNodeType::Explanation,
            MissionNodeType::Video,
            MissionNodeType::Flashcards,
        ]);
    }

    public function completePassedQuiz(User $student, MissionEnrollment $enrollment, MissionNode $node): NodeProgress
    {
        return $this->completeForTypes($student, $enrollment, $node, [MissionNodeType::Quiz], true);
    }

    /** @param list<MissionNodeType> $types */
    private function completeForTypes(
        User $student,
        MissionEnrollment $enrollment,
        MissionNode $node,
        array $types,
        bool $requiresPassedAttempt = false,
    ): NodeProgress {
        return DB::transaction(function () use ($student, $enrollment, $node, $types, $requiresPassedAttempt): NodeProgress {
            $lockedEnrollment = MissionEnrollment::query()
                ->with('assignment')
                ->lockForUpdate()
                ->findOrFail($enrollment->id);
            $lockedNode = MissionNode::query()->lockForUpdate()->findOrFail($node->id);
            $this->access->assertNode($student, $lockedEnrollment, $lockedNode);

            abort_unless(in_array($lockedNode->type, $types, true), 403);

            if ($requiresPassedAttempt) {
                abort_unless($lockedEnrollment->quizAttempts()
                    ->where('node_id', $lockedNode->id)
                    ->where('passed', true)
                    ->exists(), 403);
            }

            $progress = $lockedEnrollment->progress()
                ->where('node_id', $lockedNode->id)
                ->first();

            if ($progress !== null) {
                return $progress;
            }

            $progress = $lockedEnrollment->progress()->create([
                'node_id' => $lockedNode->id,
                'completed_at' => now(),
                'points_awarded' => 10,
            ]);

            $this->rewards->grantForFirstCompletion($student, $lockedEnrollment, $lockedNode, $progress);

            $completedNodes = $lockedEnrollment->progress()->count();
            $totalNodes = $lockedEnrollment->assignment->mission()->firstOrFail()->nodes()->count();
            $lockedEnrollment->forceFill([
                'activity_started_at' => $lockedEnrollment->activity_started_at ?? now(),
                'completed_at' => $completedNodes === $totalNodes ? now() : null,
            ])->save();

            return $progress;
        });
    }
}
