<?php

namespace App\Policies;

use App\Models\Classroom;
use App\Models\User;

class ClassroomPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Classroom $classroom): bool
    {
        return $user->isTeacher() && $classroom->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Classroom $classroom): bool
    {
        return $this->view($user, $classroom);
    }
}
