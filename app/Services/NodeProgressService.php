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
    public function __construct(private readonly StudentMissionAccess $access) {}

    public function complete(User $student, MissionEnrollment $enrollment, MissionNode $node): NodeProgress
    {
        return DB::transaction(function () use ($student, $enrollment, $node): NodeProgress {
            $lockedEnrollment = MissionEnrollment::query()
                ->with('assignment')
                ->lockForUpdate()
                ->findOrFail($enrollment->id);
            $lockedNode = MissionNode::query()->lockForUpdate()->findOrFail($node->id);
            $this->access->assertNode($student, $lockedEnrollment, $lockedNode);

            abort_unless(in_array($lockedNode->type, [
                MissionNodeType::Explanation,
                MissionNodeType::Video,
                MissionNodeType::Flashcards,
            ], true), 403);

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
