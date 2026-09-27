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

    public function publish(User $user, Mission $mission): bool
    {
        return $this->update($user, $mission);
    }

    public function duplicate(User $user, Mission $mission): bool
    {
        return $this->view($user, $mission) && $mission->status === MissionStatus::Published;
    }

    public function archive(User $user, Mission $mission): bool
    {
        return $this->view($user, $mission) && $mission->status !== MissionStatus::Archived;
    }

    public function assign(User $user, Mission $mission): bool
    {
        return $this->view($user, $mission) && $mission->status === MissionStatus::Published;
    }
}
