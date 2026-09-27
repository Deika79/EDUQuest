<?php

namespace Database\Factories;

use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MissionEnrollment> */
class MissionEnrollmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'assignment_id' => MissionAssignment::factory(),
            'student_id' => User::factory()->student(),
            'active' => true,
            'activated_at' => now(),
            'deactivated_at' => null,
            'activity_started_at' => null,
            'completed_at' => null,
        ];
    }
}
