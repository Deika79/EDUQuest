<?php

namespace Database\Factories;

use App\Enums\MissionAssignmentStatus;
use App\Models\Classroom;
use App\Models\Mission;
use App\Models\MissionAssignment;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MissionAssignment> */
class MissionAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'classroom_id' => Classroom::factory(),
            'status' => MissionAssignmentStatus::Open,
            'assigned_at' => now(),
            'closed_at' => null,
        ];
    }
}
