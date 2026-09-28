<?php

namespace App\Policies;

use App\Models\MissionEnrollment;
use App\Models\User;

class MissionEnrollmentPolicy
{
    public function view(User $user, MissionEnrollment $enrollment): bool
    {
        return $user->isTeacher()
            && $enrollment->assignment()
                ->whereHas('classroom', fn ($query) => $query->where('teacher_id', $user->id))
                ->whereHas('mission', fn ($query) => $query->where('teacher_id', $user->id))
                ->exists();
    }
}
