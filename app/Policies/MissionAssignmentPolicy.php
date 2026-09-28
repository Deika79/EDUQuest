<?php

namespace App\Policies;

use App\Enums\MissionAssignmentStatus;
use App\Models\MissionAssignment;
use App\Models\User;

class MissionAssignmentPolicy
{
    public function view(User $user, MissionAssignment $assignment): bool
    {
        return $user->isTeacher()
            && $assignment->classroom()->where('teacher_id', $user->id)->exists()
            && $assignment->mission()->where('teacher_id', $user->id)->exists();
    }

    public function close(User $user, MissionAssignment $assignment): bool
    {
        return $user->isTeacher()
            && $assignment->status === MissionAssignmentStatus::Open
            && $assignment->mission()->where('teacher_id', $user->id)->exists();
    }
}
