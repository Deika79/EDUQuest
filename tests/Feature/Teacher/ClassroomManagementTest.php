<?php

use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

test('teachers only list and view their own classes and students', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    $ownClass = Classroom::factory()->for($teacher, 'teacher')->create();
    $otherClass = Classroom::factory()->for($otherTeacher, 'teacher')->create();
    $ownStudent = User::factory()->student()->create();
    $otherStudent = User::factory()->student()->create();

    ClassroomMembership::factory()->for($ownClass)->for($ownStudent, 'student')->create();
    ClassroomMembership::factory()->for($otherClass)->for($otherStudent, 'student')->create();

    $this->actingAs($teacher)
        ->get(route('teacher.classrooms.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Classrooms/Index')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $ownClass->id));

    $this->actingAs($teacher)
        ->get(route('teacher.classrooms.show', $ownClass))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Classrooms/Show')
            ->has('memberships', 1)
            ->where('memberships.0.student.id', $ownStudent->id));

    $this->actingAs($teacher)
        ->get(route('teacher.classrooms.show', $otherClass))
        ->assertForbidden();
});

test('a teacher creates and edits a class without changing its owner', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();

    $this->actingAs($teacher)
        ->post(route('teacher.classrooms.store'), [
            'name' => 'Fractions group',
            'level' => '5 Primary',
            'subject' => 'Mathematics',
            'teacher_id' => $otherTeacher->id,
        ])
        ->assertSessionHasNoErrors();

    $classroom = Classroom::where('name', 'Fractions group')->firstOrFail();
    expect($classroom->teacher_id)->toBe($teacher->id);

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.update', $classroom), [
            'name' => 'Fractions workshop',
            'level' => '6 Primary',
            'subject' => 'Mathematics',
            'teacher_id' => $otherTeacher->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.classrooms.show', $classroom));

    expect($classroom->refresh()->name)->toBe('Fractions workshop')
        ->and($classroom->teacher_id)->toBe($teacher->id);
});

test('a teacher cannot update another teachers class even with its id', function () {
    $teacher = User::factory()->teacher()->create();
    $otherClass = Classroom::factory()->create();

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.update', $otherClass), [
            'name' => 'Taken over',
            'level' => '5 Primary',
            'subject' => 'Science',
        ])
        ->assertForbidden();

    expect($otherClass->refresh()->name)->not->toBe('Taken over');
});

test('a teacher creates a student with a temporary password and active membership', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $password = Str::password(24);

    $this->actingAs($teacher)
        ->post(route('teacher.classrooms.students.store', $classroom), [
            'name' => 'Student Alias',
            'username' => 'student.one',
            'email' => '',
            'password' => $password,
            'password_confirmation' => $password,
            'role' => UserRole::Administrator->value,
            'active' => false,
            'teacher_id' => User::factory()->teacher()->create()->id,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.classrooms.show', $classroom));

    $student = User::where('username', 'student.one')->firstOrFail();
    $membership = ClassroomMembership::whereBelongsTo($classroom)->firstOrFail();

    expect($student->role)->toBe(UserRole::Student)
        ->and($student->email)->toBeNull()
        ->and($student->active)->toBeTrue()
        ->and($student->must_change_password)->toBeTrue()
        ->and($student->created_by)->toBe($teacher->id)
        ->and(Hash::check($password, $student->password))->toBeTrue()
        ->and($membership->student_id)->toBe($student->id)
        ->and($membership->active)->toBeTrue();
});

test('duplicate usernames do not create a second student account', function () {
    $teacher = User::factory()->teacher()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $student = User::factory()->student()->create(['username' => 'shared.student']);
    $password = Str::password(24);

    $this->actingAs($teacher)
        ->post(route('teacher.classrooms.students.store', $classroom), [
            'name' => 'Duplicate account',
            'username' => $student->username,
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertSessionHasErrors('username');

    expect(User::where('username', 'shared.student')->count())->toBe(1)
        ->and($classroom->memberships()->count())->toBe(0);
});

test('an existing student joins another teachers class without duplicating the account', function () {
    $owner = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create(['created_by' => $owner->id]);
    $classroom = Classroom::factory()->for($otherTeacher, 'teacher')->create();
    $userCount = User::count();

    $this->actingAs($otherTeacher)
        ->post(route('teacher.classrooms.students.existing.store', $classroom), [
            'username' => $student->username,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.classrooms.show', $classroom));

    expect(User::count())->toBe($userCount)
        ->and($classroom->memberships()->where('student_id', $student->id)->where('active', true)->exists())
        ->toBeTrue()
        ->and($student->refresh()->created_by)->toBe($owner->id);
});

test('a class cannot contain duplicate memberships', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    ClassroomMembership::factory()->for($classroom)->for($student, 'student')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.classrooms.students.existing.store', $classroom), [
            'username' => $student->username,
        ])
        ->assertSessionHasErrors('existing_username');

    expect($classroom->memberships()->where('student_id', $student->id)->count())->toBe(1);
});

test('removing and reinstating a membership preserves it and the global account state', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $membership = ClassroomMembership::factory()->for($classroom)->for($student, 'student')->create();
    $membershipId = $membership->id;

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.memberships.update', [$classroom, $membership]), ['active' => false])
        ->assertSessionHasNoErrors();

    expect($membership->refresh()->active)->toBeFalse()
        ->and($membership->deactivated_at)->not->toBeNull()
        ->and($student->refresh()->active)->toBeTrue();

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.memberships.update', [$classroom, $membership]), ['active' => true])
        ->assertSessionHasNoErrors();

    expect($membership->refresh()->active)->toBeTrue()
        ->and($membership->deactivated_at)->toBeNull()
        ->and($membership->id)->toBe($membershipId);
});

test('a teacher cannot alter another teachers membership', function () {
    $teacher = User::factory()->teacher()->create();
    $otherClass = Classroom::factory()->create();
    $membership = ClassroomMembership::factory()->for($otherClass)->create();

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.memberships.update', [$otherClass, $membership]), ['active' => false])
        ->assertForbidden();

    expect($membership->refresh()->active)->toBeTrue();
});

test('students cannot create classes or student accounts', function () {
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->create();
    $password = Str::password(24);

    $this->actingAs($student)
        ->post(route('teacher.classrooms.store'), [
            'name' => 'Blocked',
            'level' => '5 Primary',
            'subject' => 'Science',
        ])
        ->assertForbidden();

    $this->actingAs($student)
        ->post(route('teacher.classrooms.students.store', $classroom), [
            'name' => 'Blocked student',
            'username' => 'blocked.student',
            'password' => $password,
            'password_confirmation' => $password,
        ])
        ->assertForbidden();
});
