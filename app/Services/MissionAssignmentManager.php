<?php

namespace App\Services;

use App\Enums\AssignmentClosureResult;
use App\Enums\MissionAssignmentStatus;
use App\Enums\MissionStatus;
use App\Models\Classroom;
use App\Models\Mission;
use App\Models\MissionAssignment;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MissionAssignmentManager
{
    /**
     * @param  Collection<int, Classroom>  $classrooms
     * @return Collection<int, MissionAssignment>
     */
    public function assign(Mission $mission, Collection $classrooms): Collection
    {
        return DB::transaction(function () use ($mission, $classrooms): Collection {
            $lockedMission = Mission::query()->lockForUpdate()->findOrFail($mission->id);

            if ($lockedMission->status !== MissionStatus::Published) {
                throw ValidationException::withMessages([
                    'classroom_ids' => 'Only a published mission can receive new assignments.',
                ]);
            }

            $classroomIds = $classrooms->pluck('id');
            $duplicateNames = $lockedMission->assignments()
                ->whereIn('classroom_id', $classroomIds)
                ->with('classroom:id,name')
                ->lockForUpdate()
                ->get()
                ->pluck('classroom.name');

            if ($duplicateNames->isNotEmpty()) {
                throw ValidationException::withMessages([
                    'classroom_ids' => 'Already assigned to: '.$duplicateNames->join(', ').'.',
                ]);
            }

            return $classrooms->map(function (Classroom $classroom) use ($lockedMission): MissionAssignment {
                $assignment = new MissionAssignment([
                    'status' => MissionAssignmentStatus::Open,
                    'assigned_at' => now(),
                ]);
                $assignment->mission()->associate($lockedMission);
                $assignment->classroom()->associate($classroom);
                $assignment->save();

                $assignment->nodeRewards()->createMany(
                    $lockedMission->nodes()
                        ->get(['id', 'coin_reward'])
                        ->map(fn ($node): array => [
                            'node_id' => $node->id,
                            'experience_reward' => StudentRewardService::EXPERIENCE_PER_ACTIVITY,
                            'coin_reward' => $node->coin_reward,
                        ])
                        ->all(),
                );

                $classroom->memberships()
                    ->where('active', true)
                    ->lockForUpdate()
                    ->get()
                    ->each(fn ($membership) => $assignment->enrollments()->create([
                        'student_id' => $membership->student_id,
                        'active' => true,
                        'activated_at' => now(),
                    ]));

                return $assignment;
            });
        });
    }

    public function close(MissionAssignment $assignment): AssignmentClosureResult
    {
        return DB::transaction(function () use ($assignment): AssignmentClosureResult {
            $lockedAssignment = MissionAssignment::query()
                ->with('enrollments')
                ->lockForUpdate()
                ->findOrFail($assignment->id);

            if ($lockedAssignment->status !== MissionAssignmentStatus::Open) {
                throw ValidationException::withMessages(['assignment' => 'This assignment is already closed.']);
            }

            if (! $lockedAssignment->enrollments->contains(fn ($enrollment) => $enrollment->activity_started_at !== null)) {
                $lockedAssignment->delete();

                return AssignmentClosureResult::Withdrawn;
            }

            $lockedAssignment->forceFill([
                'status' => MissionAssignmentStatus::Closed,
                'closed_at' => now(),
            ])->save();
            $lockedAssignment->enrollments()->where('active', true)->update([
                'active' => false,
                'deactivated_at' => now(),
            ]);

            return AssignmentClosureResult::Closed;
        });
    }
}
