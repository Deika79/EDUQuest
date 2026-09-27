<?php

namespace App\Services;

use App\Enums\MissionAssignmentStatus;
use App\Models\ClassroomMembership;
use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;

class MissionEnrollmentSynchronizer
{
    public function sync(ClassroomMembership $membership): void
    {
        $assignments = MissionAssignment::query()
            ->where('classroom_id', $membership->classroom_id)
            ->where('status', MissionAssignmentStatus::Open)
            ->lockForUpdate()
            ->get();

        foreach ($assignments as $assignment) {
            $enrollment = MissionEnrollment::query()
                ->where('assignment_id', $assignment->id)
                ->where('student_id', $membership->student_id)
                ->first() ?? new MissionEnrollment;

            if (! $membership->active && ! $enrollment->exists) {
                continue;
            }

            $enrollment->assignment()->associate($assignment);
            $enrollment->student()->associate($membership->student_id);
            $enrollment->forceFill([
                'active' => $membership->active,
                'activated_at' => $membership->active
                    ? now()
                    : ($enrollment->activated_at ?? $membership->activated_at),
                'deactivated_at' => $membership->active ? null : now(),
            ]);
            $enrollment->save();
        }
    }
}
