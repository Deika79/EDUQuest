<?php

namespace App\Policies;

use App\Models\StudentCosmeticItem;
use App\Models\User;

class StudentCosmeticItemPolicy
{
    public function update(User $user, StudentCosmeticItem $ownership): bool
    {
        return $user->isStudent() && $ownership->student_id === $user->id;
    }
}
