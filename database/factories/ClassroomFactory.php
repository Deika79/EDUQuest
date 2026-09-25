<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Classroom> */
class ClassroomFactory extends Factory
{
    public function definition(): array
    {
        return [
            'teacher_id' => User::factory()->teacher(),
            'name' => fake()->words(3, true),
            'level' => fake()->randomElement(['4 Primary', '5 Primary', '6 Primary']),
            'subject' => fake()->randomElement(['Mathematics', 'Science', 'Language']),
            'archived_at' => null,
        ];
    }
}
