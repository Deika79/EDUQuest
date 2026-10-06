<?php

use App\Enums\MissionMapTheme;
use App\Enums\MissionNodeType;
use App\Enums\MissionStatus;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;
use App\Models\User;
use App\Services\MissionLifecycle;
use App\Services\MissionNodeWriter;
use Inertia\Testing\AssertableInertia as Assert;

function r03NodePayload(string $type): array
{
    return match ($type) {
        'explanation' => [
            'title' => 'Read the briefing',
            'type' => 'explanation',
            'body' => 'A complete explanation for the learner.',
        ],
        'video' => [
            'title' => 'Watch the example',
            'type' => 'video',
            'video_provider' => 'youtube',
            'video_reference' => 'dQw4w9WgXcQ',
        ],
        'quiz' => [
            'title' => 'Check your understanding',
            'type' => 'quiz',
            'pass_threshold' => 80,
            'questions' => [[
                'statement' => 'Which value is one half?',
                'explanation' => 'One divided by two is one half.',
                'options' => [
                    ['text' => '1/2', 'is_correct' => true],
                    ['text' => '1/3', 'is_correct' => false],
                ],
            ]],
        ],
        'flashcards' => [
            'title' => 'Review key terms',
            'type' => 'flashcards',
            'flashcards' => [
                ['front' => 'Numerator', 'back' => 'The top number.'],
                ['front' => 'Denominator', 'back' => 'The bottom number.'],
            ],
        ],
    };
}

/** @param list<string> $types */
function r03ReadyMission(User $teacher, array $types = ['explanation']): Mission
{
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $writer = app(MissionNodeWriter::class);

    foreach ($types as $type) {
        $writer->save($mission, null, r03NodePayload($type));
    }

    return $mission->fresh();
}

test('an incomplete draft cannot be published', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.publish', $mission))
        ->assertSessionHasErrors('mission');

    expect($mission->refresh()->status)->toBe(MissionStatus::Draft)
        ->and($mission->published_at)->toBeNull();
});

test('a ready draft can be published and its content becomes immutable', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher);
    $node = $mission->nodes()->firstOrFail();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.publish', $mission))
        ->assertSessionHasNoErrors();

    expect($mission->refresh()->status)->toBe(MissionStatus::Published)
        ->and($mission->published_at)->not->toBeNull();

    $this->actingAs($teacher)
        ->patch(route('teacher.missions.update', $mission), [
            'title' => 'Changed',
            'description' => $mission->description,
            'subject' => $mission->subject,
            'level' => $mission->level,
            'map_theme' => $mission->map_theme->value,
        ])
        ->assertForbidden();
    $this->actingAs($teacher)
        ->post(route('teacher.missions.nodes.store', $mission), r03NodePayload('explanation'))
        ->assertForbidden();
    $this->actingAs($teacher)
        ->patch(route('teacher.missions.nodes.update', [$mission, $node]), r03NodePayload('explanation'))
        ->assertForbidden();
    $this->actingAs($teacher)
        ->delete(route('teacher.missions.nodes.destroy', [$mission, $node]))
        ->assertForbidden();
    $this->actingAs($teacher)
        ->patch(route('teacher.missions.nodes.move', [$mission, $node]), ['direction' => 'up'])
        ->assertForbidden();

    expect($mission->refresh()->title)->not->toBe('Changed')
        ->and($mission->nodes()->count())->toBe(1);
});

test('a published mission is duplicated faithfully as an independent draft', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher, array_column(MissionNodeType::cases(), 'value'));
    $mission->forceFill(['map_theme' => MissionMapTheme::OldWest])->save();
    app(MissionLifecycle::class)->publish($mission);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.duplicate', $mission))
        ->assertSessionHasNoErrors();

    $copy = Mission::query()->where('teacher_id', $teacher->id)->whereKeyNot($mission->id)->firstOrFail();
    $copy->load(['nodes.questions.options', 'nodes.flashcards']);

    expect($copy->status)->toBe(MissionStatus::Draft)
        ->and($copy->published_at)->toBeNull()
        ->and($copy->title)->toBe($mission->title.' (copy)')
        ->and($copy->map_theme)->toBe(MissionMapTheme::OldWest)
        ->and($copy->nodes->pluck('type')->all())->toBe(MissionNodeType::cases())
        ->and($copy->nodes->pluck('position')->all())->toBe([1, 2, 3, 4])
        ->and($copy->nodes[1]->video_id)->toBe('dQw4w9WgXcQ')
        ->and($copy->nodes[2]->questions)->toHaveCount(1)
        ->and($copy->nodes[2]->questions[0]->options)->toHaveCount(2)
        ->and($copy->nodes[3]->flashcards)->toHaveCount(2)
        ->and($mission->fresh()->status)->toBe(MissionStatus::Published)
        ->and($mission->nodes()->count())->toBe(4);
});

test('a mission can be assigned to two owned classes and active students are enrolled', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher);
    app(MissionLifecycle::class)->publish($mission);
    $classes = Classroom::factory()->count(2)->for($teacher, 'teacher')->create();
    $activeStudent = User::factory()->student()->create();
    $inactiveStudent = User::factory()->student()->create();

    foreach ($classes as $classroom) {
        ClassroomMembership::factory()->for($classroom)->for($activeStudent, 'student')->create();
        ClassroomMembership::factory()->inactive()->for($classroom)->for($inactiveStudent, 'student')->create();
    }

    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), [
            'classroom_ids' => $classes->pluck('id')->all(),
        ])
        ->assertSessionHasNoErrors();

    expect($mission->assignments()->count())->toBe(2)
        ->and(MissionEnrollment::query()->count())->toBe(2)
        ->and(MissionEnrollment::query()->where('student_id', $activeStudent->id)->count())->toBe(2)
        ->and(MissionEnrollment::query()->where('student_id', $inactiveStudent->id)->count())->toBe(0);

    $this->actingAs($teacher)
        ->get(route('teacher.missions.show', $mission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Missions/Show')
            ->has('assignments', 2)
            ->where('assignments.0.status', 'open')
            ->where('assignments.0.enrollments_count', 1)
            ->where('assignments.0.active_enrollments_count', 1));
});

test('foreign mission and class ids and duplicate pairs are rejected', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher);
    $otherMission = r03ReadyMission($otherTeacher);
    app(MissionLifecycle::class)->publish($mission);
    app(MissionLifecycle::class)->publish($otherMission);
    $ownClass = Classroom::factory()->for($teacher, 'teacher')->create();
    $foreignClass = Classroom::factory()->for($otherTeacher, 'teacher')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$foreignClass->id]])
        ->assertSessionHasErrors('classroom_ids');
    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $otherMission), ['classroom_ids' => [$ownClass->id]])
        ->assertForbidden();
    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$ownClass->id]])
        ->assertSessionHasNoErrors();
    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$ownClass->id]])
        ->assertSessionHasErrors('classroom_ids');

    expect($mission->assignments()->count())->toBe(1);
});

test('archiving blocks new assignments but keeps existing ones', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher);
    app(MissionLifecycle::class)->publish($mission);
    $firstClass = Classroom::factory()->for($teacher, 'teacher')->create();
    $secondClass = Classroom::factory()->for($teacher, 'teacher')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$firstClass->id]])
        ->assertSessionHasNoErrors();
    $this->actingAs($teacher)
        ->post(route('teacher.missions.archive', $mission))
        ->assertSessionHasNoErrors();
    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$secondClass->id]])
        ->assertForbidden();

    expect($mission->refresh()->status)->toBe(MissionStatus::Archived)
        ->and($mission->assignments()->count())->toBe(1)
        ->and($mission->assignments()->firstOrFail()->status->value)->toBe('open');
});

test('membership additions removals and reinstatements synchronize open assignments', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher);
    app(MissionLifecycle::class)->publish($mission);
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $assignment = MissionAssignment::factory()
        ->for($mission)
        ->for($classroom)
        ->create();
    $student = User::factory()->student()->create();

    $this->actingAs($teacher)
        ->post(route('teacher.classrooms.students.existing.store', $classroom), [
            'username' => $student->username,
        ])
        ->assertSessionHasNoErrors();

    $membership = $classroom->memberships()->where('student_id', $student->id)->firstOrFail();
    $enrollment = $assignment->enrollments()->where('student_id', $student->id)->firstOrFail();

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.memberships.update', [$classroom, $membership]), ['active' => false])
        ->assertSessionHasNoErrors();

    expect($enrollment->refresh()->active)->toBeFalse()
        ->and($enrollment->deactivated_at)->not->toBeNull();

    $this->actingAs($teacher)
        ->patch(route('teacher.classrooms.memberships.update', [$classroom, $membership]), ['active' => true])
        ->assertSessionHasNoErrors();

    expect($enrollment->refresh()->active)->toBeTrue()
        ->and($enrollment->deactivated_at)->toBeNull()
        ->and($assignment->enrollments()->where('student_id', $student->id)->count())->toBe(1);
});

test('an open assignment without activity can be withdrawn', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = r03ReadyMission($teacher);
    app(MissionLifecycle::class)->publish($mission);
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $student = User::factory()->student()->create();
    ClassroomMembership::factory()->for($classroom)->for($student, 'student')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$classroom->id]]);
    $assignment = $mission->assignments()->firstOrFail();

    $this->actingAs($teacher)
        ->delete(route('teacher.missions.assignments.destroy', [$mission, $assignment]))
        ->assertSessionHasNoErrors();

    $this->assertDatabaseMissing('mission_assignments', ['id' => $assignment->id]);
    $this->assertDatabaseMissing('mission_enrollments', ['assignment_id' => $assignment->id]);
});

test('a teacher cannot close an assignment through another mission id', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    $ownMission = r03ReadyMission($teacher);
    $foreignMission = r03ReadyMission($otherTeacher);
    app(MissionLifecycle::class)->publish($foreignMission);
    $foreignClass = Classroom::factory()->for($otherTeacher, 'teacher')->create();
    $assignment = MissionAssignment::factory()->for($foreignMission)->for($foreignClass)->create();

    $this->actingAs($teacher)
        ->delete(route('teacher.missions.assignments.destroy', [$ownMission, $assignment]))
        ->assertNotFound();

    $this->assertDatabaseHas('mission_assignments', ['id' => $assignment->id]);
});

test('students cannot publish archive duplicate or assign missions', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $mission = r03ReadyMission($teacher);
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();

    $this->actingAs($student)
        ->post(route('teacher.missions.publish', $mission))
        ->assertForbidden();
    $this->actingAs($student)
        ->post(route('teacher.missions.archive', $mission))
        ->assertForbidden();
    $this->actingAs($student)
        ->post(route('teacher.missions.duplicate', $mission))
        ->assertForbidden();
    $this->actingAs($student)
        ->post(route('teacher.missions.assignments.store', $mission), ['classroom_ids' => [$classroom->id]])
        ->assertForbidden();
});
