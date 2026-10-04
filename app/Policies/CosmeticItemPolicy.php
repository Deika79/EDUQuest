<?php

namespace App\Policies;

use App\Models\CosmeticItem;
use App\Models\User;

class CosmeticItemPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->isStudent();
    }

    public function purchase(User $user, CosmeticItem $item): bool
    {
        return $user->isStudent() && $item->active;
    }
}
