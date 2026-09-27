<?php

namespace App\Services;

use App\Enums\MissionAssignmentStatus;
use App\Models\ClassroomMembership;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\User;

class StudentMissionAccess
{
    public function assertEnrollment(User $student, MissionEnrollment $enrollment): MissionEnrollment
    {
        abort_unless($student->active && $student->isStudent(), 403);
        abort_unless($enrollment->student_id === $student->id && $enrollment->active, 403);

        $enrollment->loadMissing('assignment');
        abort_unless($enrollment->assignment->status === MissionAssignmentStatus::Open, 403);

        $hasActiveMembership = ClassroomMembership::query()
            ->where('classroom_id', $enrollment->assignment->classroom_id)
            ->where('student_id', $student->id)
            ->where('active', true)
            ->exists();
        abort_unless($hasActiveMembership, 403);

        return $enrollment;
    }

    public function assertNode(User $student, MissionEnrollment $enrollment, MissionNode $node): MissionEnrollment
    {
        $enrollment = $this->assertEnrollment($student, $enrollment);
        abort_unless($node->mission_id === $enrollment->assignment->mission_id, 404);

        $previousNodeId = MissionNode::query()
            ->where('mission_id', $node->mission_id)
            ->where('position', '<', $node->position)
            ->orderByDesc('position')
            ->value('id');

        if ($previousNodeId !== null) {
            abort_unless(
                $enrollment->progress()->where('node_id', $previousNodeId)->exists(),
                403,
            );
        }

        return $enrollment;
    }

    /** @return list<array{id: int, position: int, type: string, title: string, status: string}> */
    public function map(MissionEnrollment $enrollment): array
    {
        $nodes = MissionNode::query()
            ->where('mission_id', $enrollment->assignment->mission_id)
            ->orderBy('position')
            ->get(['id', 'mission_id', 'position', 'type', 'title']);
        $completedIds = $enrollment->progress()->pluck('node_id')->all();
        $completedLookup = array_fill_keys($completedIds, true);
        $previousCompleted = true;

        return array_values($nodes->map(function (MissionNode $node) use (&$previousCompleted, $completedLookup): array {
            $completed = isset($completedLookup[$node->id]);
            $status = match (true) {
                $completed => 'completed',
                ! $previousCompleted => 'locked',
                default => 'available',
            };
            $previousCompleted = $completed;

            return [
                'id' => $node->id,
                'position' => $node->position,
                'type' => $node->type->value,
                'title' => $node->title,
                'status' => $status,
            ];
        })->all());
    }
}
