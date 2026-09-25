<?php

use App\Models\User;

test('each role is redirected to its own dashboard', function (string $state, string $route) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)
        ->get(route('dashboard'))
        ->assertRedirect(route($route));
})->with([
    ['administrator', 'admin.dashboard'],
    ['teacher', 'teacher.dashboard'],
    ['student', 'student.dashboard'],
]);

test('non administrators cannot open the administration panel', function (string $state) {
    $user = User::factory()->{$state}()->create();

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
})->with(['teacher', 'student']);

test('students cannot open the teacher dashboard', function () {
    $student = User::factory()->student()->create();

    $this->actingAs($student)
        ->get(route('teacher.dashboard'))
        ->assertForbidden();
});
