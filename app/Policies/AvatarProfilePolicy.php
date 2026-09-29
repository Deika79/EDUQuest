<?php

namespace App\Policies;

use App\Models\AvatarProfile;
use App\Models\User;

class AvatarProfilePolicy
{
    public function create(User $user): bool
    {
        return $user->isStudent();
    }

    public function view(User $user, AvatarProfile $profile): bool
    {
        return $user->isStudent() && $profile->student_id === $user->id;
    }

    public function update(User $user, AvatarProfile $profile): bool
    {
        return $this->view($user, $profile);
    }
}
