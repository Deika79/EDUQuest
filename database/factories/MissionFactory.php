<?php

namespace Database\Factories;

use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Models\Mission;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Mission> */
class MissionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'title' => fake()->sentence(4),
            'description' => fake()->paragraph(),
            'subject' => fake()->randomElement(['Mathematics', 'Science', 'Language']),
            'level' => fake()->randomElement(['4 Primary', '5 Primary', '6 Primary']),
            'status' => MissionStatus::Draft,
            'source' => MissionSource::Manual,
            'published_at' => null,
        ];
    }
}
