<?php

namespace Database\Factories;

use App\Enums\UserRole;
use App\Models\AvatarProfile;
use App\Models\User;
use App\Support\AvatarOptions;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<AvatarProfile> */
class AvatarProfileFactory extends Factory
{
    protected $model = AvatarProfile::class;

    /** @return array<string, mixed> */
    public function definition(): array
    {
        return [
            'student_id' => User::factory()->state(['role' => UserRole::Student]),
            ...AvatarOptions::defaultSelection(),
            'setup_completed_at' => now(),
        ];
    }
}
