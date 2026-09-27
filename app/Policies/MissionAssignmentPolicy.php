<?php

namespace App\Policies;

use App\Enums\MissionAssignmentStatus;
use App\Models\MissionAssignment;
use App\Models\User;

class MissionAssignmentPolicy
{
    public function close(User $user, MissionAssignment $assignment): bool
    {
        return $user->isTeacher()
            && $assignment->status === MissionAssignmentStatus::Open
            && $assignment->mission()->where('teacher_id', $user->id)->exists();
    }
}
