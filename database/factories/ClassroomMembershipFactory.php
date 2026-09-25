<?php

namespace Database\Factories;

use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ClassroomMembership> */
class ClassroomMembershipFactory extends Factory
{
    public function definition(): array
    {
        return [
            'classroom_id' => Classroom::factory(),
            'student_id' => User::factory()->student(),
            'active' => true,
            'activated_at' => now(),
            'deactivated_at' => null,
        ];
    }

    public function inactive(): static
    {
        return $this->state(fn () => [
            'active' => false,
            'deactivated_at' => now(),
        ]);
    }
}
