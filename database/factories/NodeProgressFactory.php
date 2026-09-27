<?php

namespace Database\Factories;

use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\NodeProgress;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<NodeProgress> */
class NodeProgressFactory extends Factory
{
    public function definition(): array
    {
        return [
            'enrollment_id' => MissionEnrollment::factory(),
            'node_id' => MissionNode::factory(),
            'completed_at' => now(),
            'points_awarded' => 10,
        ];
    }
}
