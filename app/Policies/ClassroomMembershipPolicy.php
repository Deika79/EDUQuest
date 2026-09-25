<?php

namespace App\Policies;

use App\Models\ClassroomMembership;
use App\Models\User;

class ClassroomMembershipPolicy
{
    public function update(User $user, ClassroomMembership $membership): bool
    {
        return $user->isTeacher() && $membership->classroom()->where('teacher_id', $user->id)->exists();
    }
}
