<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('an administrator can view the teacher administration panel', function () {
    $administrator = User::factory()->administrator()->create();
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($administrator)
        ->get(route('admin.dashboard'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/Dashboard')
            ->has('teachers', 1)
            ->where('teachers.0.id', $teacher->id));
});

test('an administrator can create a teacher with a temporary password', function () {
    $administrator = User::factory()->administrator()->create();
    $password = Str::password(24);

    $this->actingAs($administrator)
        ->post(route('admin.teachers.store'), [
            'name' => 'Test Teacher',
            'username' => 'teacher.test',
            'email' => 'teacher.test@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    $teacher = User::where('username', 'teacher.test')->firstOrFail();

    expect($teacher->role)->toBe(UserRole::Teacher)
        ->and($teacher->active)->toBeTrue()
        ->and($teacher->must_change_password)->toBeTrue()
        ->and($teacher->created_by)->toBe($administrator->id)
        ->and(Hash::check($password, $teacher->password))->toBeTrue();
});

test('a role field in the teacher request cannot elevate privileges', function () {
    $administrator = User::factory()->administrator()->create();
    $password = Str::password(24);

    $this->actingAs($administrator)->post(route('admin.teachers.store'), [
        'name' => 'Test Teacher',
        'username' => 'teacher.safe',
        'email' => 'teacher.safe@example.test',
        'password' => $password,
        'password_confirmation' => $password,
        'role' => UserRole::Administrator->value,
        'active' => false,
    ])->assertSessionHasNoErrors();

    $teacher = User::where('username', 'teacher.safe')->firstOrFail();

    expect($teacher->role)->toBe(UserRole::Teacher)
        ->and($teacher->active)->toBeTrue();
});

test('teachers and students cannot manage teacher accounts', function (string $role) {
    $user = User::factory()->create(['role' => $role]);
    $password = Str::password(24);

    $this->actingAs($user)
        ->post(route('admin.teachers.store'), [
            'name' => 'Blocked Teacher',
            'username' => 'blocked.teacher',
            'email' => 'blocked@example.test',
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertForbidden();

    $this->assertDatabaseMissing('users', ['username' => 'blocked.teacher']);
})->with([UserRole::Teacher->value, UserRole::Student->value]);

test('an administrator can deactivate a teacher', function () {
    $administrator = User::factory()->administrator()->create();
    $teacher = User::factory()->teacher()->create();

    $this->actingAs($administrator)
        ->patch(route('admin.teachers.update', $teacher), ['active' => false])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('admin.dashboard'));

    expect($teacher->refresh()->active)->toBeFalse();
});
