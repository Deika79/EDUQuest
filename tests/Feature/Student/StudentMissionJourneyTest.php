<?php

use App\Enums\MissionAssignmentStatus;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;
use App\Models\NodeProgress;
use App\Models\User;
use App\Services\MissionAssignmentManager;
use App\Services\MissionLifecycle;
use App\Services\MissionNodeWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function studentJourneyPayload(string $type): array
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
            'pass_threshold' => 70,
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

/**
 * @param  list<string>  $types
 * @return array{teacher: User, student: User, classroom: Classroom, membership: ClassroomMembership, mission: Mission, assignment: MissionAssignment, enrollment: MissionEnrollment}
 */
function createStudentJourney(array $types = ['explanation', 'video', 'flashcards']): array
{
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $membership = ClassroomMembership::factory()->for($classroom)->for($student, 'student')->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $writer = app(MissionNodeWriter::class);

    foreach ($types as $type) {
        $writer->save($mission, null, studentJourneyPayload($type));
    }

    app(MissionLifecycle::class)->publish($mission);
    $assignment = app(MissionAssignmentManager::class)->assign($mission, collect([$classroom]))->firstOrFail();
    $enrollment = $assignment->enrollments()->where('student_id', $student->id)->firstOrFail();

    return compact('teacher', 'student', 'classroom', 'membership', 'mission', 'assignment', 'enrollment');
}

/** @return TestResponse<RedirectResponse> */
function completeStudentNode(User $student, MissionEnrollment $enrollment, int $nodeId, array $data = []): TestResponse
{
    return actingAs($student)->post(
        route('student.missions.nodes.complete', [$enrollment, $nodeId]),
        ['confirmed' => true, ...$data],
    );
}

test('students only list missions with an active enrollment membership and open assignment', function () {
    $journey = createStudentJourney(['explanation']);
    $closedJourney = createStudentJourney(['explanation']);
    $closedJourney['enrollment']->forceFill(['student_id' => $journey['student']->id])->save();
    $closedJourney['assignment']->forceFill(['status' => MissionAssignmentStatus::Closed])->save();

    $inactiveJourney = createStudentJourney(['explanation']);
    $inactiveJourney['enrollment']->forceFill([
        'student_id' => $journey['student']->id,
        'active' => false,
    ])->save();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Dashboard')
            ->has('missions', 1)
            ->where('missions.0.enrollment_id', $journey['enrollment']->id));
});

test('the mission map exposes node metadata but no locked content', function () {
    $journey = createStudentJourney();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.show', $journey['enrollment']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Missions/Show')
            ->has('nodes', 3)
            ->where('nodes.0.status', 'available')
            ->where('nodes.1.status', 'locked')
            ->where('nodes.2.status', 'locked')
            ->missing('nodes.0.body')
            ->missing('nodes.1.video_id')
            ->missing('nodes.2.flashcards'));
});

test('a student completes explanation video and all flashcards in order', function () {
    $journey = createStudentJourney();
    $nodes = $journey['mission']->nodes()->get();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $nodes[0]]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Missions/Activity')
            ->where('node.body', 'A complete explanation for the learner.'));
    completeStudentNode($journey['student'], $journey['enrollment'], $nodes[0]->id)
        ->assertSessionHasNoErrors();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $nodes[1]]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('node.video.external_url', 'https://www.youtube.com/watch?v=dQw4w9WgXcQ'));
    completeStudentNode($journey['student'], $journey['enrollment'], $nodes[1]->id)
        ->assertSessionHasNoErrors();

    $cardIds = $nodes[2]->flashcards()->pluck('id')->all();
    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $nodes[2]]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->has('node.flashcards', 2));
    completeStudentNode($journey['student'], $journey['enrollment'], $nodes[2]->id, [
        'flashcard_ids' => $cardIds,
    ])->assertSessionHasNoErrors();

    expect($journey['enrollment']->progress()->count())->toBe(3)
        ->and((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(30)
        ->and($journey['enrollment']->refresh()->activity_started_at)->not->toBeNull()
        ->and($journey['enrollment']->completed_at)->not->toBeNull();
});

test('locked nodes reject direct viewing and completion requests', function () {
    $journey = createStudentJourney(['explanation', 'video']);
    $video = $journey['mission']->nodes()->where('position', 2)->firstOrFail();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $video]))
        ->assertForbidden();
    completeStudentNode($journey['student'], $journey['enrollment'], $video->id)
        ->assertForbidden();

    expect($journey['enrollment']->progress()->count())->toBe(0);
});

test('students cannot cross enrollment mission or role boundaries', function () {
    $first = createStudentJourney(['explanation']);
    $second = createStudentJourney(['explanation']);
    $foreignNode = $second['mission']->nodes()->firstOrFail();

    $this->actingAs($first['student'])
        ->get(route('student.missions.show', $second['enrollment']))
        ->assertForbidden();
    $this->actingAs($first['student'])
        ->get(route('student.missions.nodes.show', [$first['enrollment'], $foreignNode]))
        ->assertNotFound();
    $this->actingAs($first['teacher'])
        ->get(route('student.missions.show', $first['enrollment']))
        ->assertForbidden();

    expect(NodeProgress::query()->count())->toBe(0);
});

test('flashcards from another node cannot complete an activity', function () {
    $journey = createStudentJourney(['flashcards']);
    $other = createStudentJourney(['flashcards']);
    $node = $journey['mission']->nodes()->firstOrFail();
    $foreignCardIds = $other['mission']->nodes()->firstOrFail()->flashcards()->pluck('id')->all();

    completeStudentNode($journey['student'], $journey['enrollment'], $node->id, [
        'flashcard_ids' => $foreignCardIds,
    ])->assertSessionHasErrors('flashcard_ids');

    expect($journey['enrollment']->progress()->count())->toBe(0);
});

test('repeated completion awards points only once', function () {
    $journey = createStudentJourney(['explanation']);
    $node = $journey['mission']->nodes()->firstOrFail();

    completeStudentNode($journey['student'], $journey['enrollment'], $node->id)->assertSessionHasNoErrors();
    completeStudentNode($journey['student'], $journey['enrollment'], $node->id)->assertSessionHasNoErrors();

    expect($journey['enrollment']->progress()->count())->toBe(1)
        ->and((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(10);
});

test('removal blocks access and reinstatement preserves progress', function () {
    $journey = createStudentJourney(['explanation', 'video']);
    $firstNode = $journey['mission']->nodes()->firstOrFail();
    completeStudentNode($journey['student'], $journey['enrollment'], $firstNode->id)->assertSessionHasNoErrors();

    $this->actingAs($journey['teacher'])
        ->patch(route('teacher.classrooms.memberships.update', [$journey['classroom'], $journey['membership']]), [
            'active' => false,
        ])
        ->assertSessionHasNoErrors();
    $this->actingAs($journey['student'])
        ->get(route('student.missions.show', $journey['enrollment']))
        ->assertForbidden();

    $this->actingAs($journey['teacher'])
        ->patch(route('teacher.classrooms.memberships.update', [$journey['classroom'], $journey['membership']]), [
            'active' => true,
        ])
        ->assertSessionHasNoErrors();
    $this->actingAs($journey['student'])
        ->get(route('student.missions.show', $journey['enrollment']))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('enrollment.completed_nodes', 1)
            ->where('enrollment.points', 10)
            ->where('nodes.0.status', 'completed')
            ->where('nodes.1.status', 'available'));

    expect($journey['enrollment']->progress()->count())->toBe(1);
});

test('an available questionnaire still stops the linear path until it is passed', function () {
    $journey = createStudentJourney(['explanation', 'quiz', 'explanation']);
    $nodes = $journey['mission']->nodes()->get();
    completeStudentNode($journey['student'], $journey['enrollment'], $nodes[0]->id)->assertSessionHasNoErrors();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.show', $journey['enrollment']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nodes.1.status', 'available')
            ->where('nodes.2.status', 'locked'));
    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $nodes[1]]))
        ->assertOk();
    completeStudentNode($journey['student'], $journey['enrollment'], $nodes[1]->id)
        ->assertForbidden();
    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $nodes[2]]))
        ->assertForbidden();
});

test('closing an assignment after activity preserves history', function () {
    $journey = createStudentJourney(['explanation']);
    $node = $journey['mission']->nodes()->firstOrFail();
    completeStudentNode($journey['student'], $journey['enrollment'], $node->id)->assertSessionHasNoErrors();

    $this->actingAs($journey['teacher'])
        ->delete(route('teacher.missions.assignments.destroy', [$journey['mission'], $journey['assignment']]))
        ->assertSessionHasNoErrors();

    expect($journey['assignment']->refresh()->status)->toBe(MissionAssignmentStatus::Closed)
        ->and($journey['enrollment']->refresh()->active)->toBeFalse()
        ->and($journey['enrollment']->progress()->count())->toBe(1)
        ->and((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(10);
});
