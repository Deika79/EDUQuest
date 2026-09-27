<?php

namespace Database\Factories;

use App\Enums\MissionNodeType;
use App\Models\Mission;
use App\Models\MissionNode;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<MissionNode> */
class MissionNodeFactory extends Factory
{
    public function definition(): array
    {
        return [
            'mission_id' => Mission::factory(),
            'position' => 1,
            'type' => MissionNodeType::Explanation,
            'title' => fake()->sentence(3),
            'body' => fake()->paragraph(),
            'video_provider' => null,
            'video_id' => null,
            'pass_threshold' => null,
        ];
    }
}
