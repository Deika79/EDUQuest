<?php

namespace App\Policies;

use App\Enums\MissionStatus;
use App\Models\Mission;
use App\Models\User;

class MissionPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isTeacher();
    }

    public function view(User $user, Mission $mission): bool
    {
        return $user->isTeacher() && $mission->teacher_id === $user->id;
    }

    public function create(User $user): bool
    {
        return $user->isTeacher();
    }

    public function update(User $user, Mission $mission): bool
    {
        return $this->view($user, $mission) && $mission->status === MissionStatus::Draft;
    }
}
