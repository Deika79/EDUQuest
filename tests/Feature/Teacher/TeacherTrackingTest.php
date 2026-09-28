<?php

use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\User;
use App\Services\MissionAssignmentManager;
use App\Services\MissionLifecycle;
use App\Services\MissionNodeWriter;
use Illuminate\Database\Eloquent\Collection;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

/**
 * @return array{
 *     teacher: User,
 *     classroom: Classroom,
 *     mission: Mission,
 *     assignment: MissionAssignment,
 *     firstStudent: User,
 *     secondStudent: User,
 *     firstMembership: ClassroomMembership,
 *     firstEnrollment: MissionEnrollment,
 *     secondEnrollment: MissionEnrollment,
 *     nodes: Collection<int, MissionNode>,
 *     quiz: MissionNode
 * }
 */
function createTeacherTrackingJourney(): array
{
    $teacher = User::factory()->teacher()->create();
    $firstStudent = User::factory()->student()->create(['name' => 'Alpha Learner']);
    $secondStudent = User::factory()->student()->create(['name' => 'Beta Learner']);
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create(['name' => 'Tracking class']);
    $firstMembership = ClassroomMembership::factory()->for($classroom)->for($firstStudent, 'student')->create();
    ClassroomMembership::factory()->for($classroom)->for($secondStudent, 'student')->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create(['title' => 'Tracking mission']);
    $writer = app(MissionNodeWriter::class);

    $writer->save($mission, null, [
        'title' => 'Read first',
        'type' => 'explanation',
        'body' => 'A complete explanation for tracking.',
    ]);
    $quiz = $writer->save($mission, null, [
        'title' => 'Fractions quiz',
        'type' => 'quiz',
        'pass_threshold' => 70,
        'questions' => [
            [
                'statement' => 'Question one',
                'explanation' => 'Explanation one',
                'options' => [
                    ['text' => 'Correct one', 'is_correct' => true],
                    ['text' => 'Wrong one', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Question two',
                'explanation' => 'Explanation two',
                'options' => [
                    ['text' => 'Correct two', 'is_correct' => true],
                    ['text' => 'Wrong two', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Question three',
                'explanation' => 'Explanation three',
                'options' => [
                    ['text' => 'Correct three', 'is_correct' => true],
                    ['text' => 'Wrong three', 'is_correct' => false],
                ],
            ],
        ],
    ]);
    $writer->save($mission, null, [
        'title' => 'Finish reading',
        'type' => 'explanation',
        'body' => 'The final explanation.',
    ]);

    app(MissionLifecycle::class)->publish($mission);
    $assignment = app(MissionAssignmentManager::class)->assign($mission, collect([$classroom]))->firstOrFail();
    $firstEnrollment = $assignment->enrollments()->where('student_id', $firstStudent->id)->firstOrFail();
    $secondEnrollment = $assignment->enrollments()->where('student_id', $secondStudent->id)->firstOrFail();

    return [
        'teacher' => $teacher,
        'classroom' => $classroom,
        'mission' => $mission,
        'assignment' => $assignment,
        'firstStudent' => $firstStudent,
        'secondStudent' => $secondStudent,
        'firstMembership' => $firstMembership,
        'firstEnrollment' => $firstEnrollment,
        'secondEnrollment' => $secondEnrollment,
        'nodes' => $mission->nodes()->get(),
        'quiz' => $quiz,
    ];
}

/** @return list<array{question_id: int, option_id: int}> */
function trackingQuizAnswers(MissionNode $quiz, int $correctAnswers): array
{
    return $quiz->questions()->with('options')->get()->values()->map(
        function ($question, int $index) use ($correctAnswers): array {
            $option = $question->options->firstWhere('is_correct', $index < $correctAnswers)
                ?? $question->options->firstOrFail();

            return ['question_id' => $question->id, 'option_id' => $option->id];
        },
    )->all();
}

/** @param array<string, mixed> $journey */
function prepareTeacherTrackingActivity(array $journey): void
{
    $firstNode = $journey['nodes'][0];
    $lastNode = $journey['nodes'][2];
    $quiz = $journey['quiz'];

    actingAs($journey['firstStudent'])
        ->post(route('student.missions.nodes.complete', [$journey['firstEnrollment'], $firstNode]), ['confirmed' => true]);
    actingAs($journey['firstStudent'])
        ->post(route('student.missions.nodes.quiz-attempts.store', [$journey['firstEnrollment'], $quiz]), [
            'answers' => trackingQuizAnswers($quiz, 2),
        ]);

    actingAs($journey['secondStudent'])
        ->post(route('student.missions.nodes.complete', [$journey['secondEnrollment'], $firstNode]), ['confirmed' => true]);
    actingAs($journey['secondStudent'])
        ->post(route('student.missions.nodes.quiz-attempts.store', [$journey['secondEnrollment'], $quiz]), [
            'answers' => trackingQuizAnswers($quiz, 3),
        ]);
    actingAs($journey['secondStudent'])
        ->post(route('student.missions.nodes.quiz-attempts.store', [$journey['secondEnrollment'], $quiz]), [
            'answers' => trackingQuizAnswers($quiz, 1),
        ]);
    actingAs($journey['secondStudent'])
        ->post(route('student.missions.nodes.complete', [$journey['secondEnrollment'], $lastNode]), ['confirmed' => true]);

    actingAs($journey['teacher'])
        ->patch(
            route('teacher.classrooms.memberships.update', [$journey['classroom'], $journey['firstMembership']]),
            ['active' => false],
        );
}

test('a teacher sees separate progress scoring and inactive history for an assigned mission', function () {
    $journey = createTeacherTrackingJourney();
    prepareTeacherTrackingActivity($journey);

    $progressRows = $journey['firstEnrollment']->progress()->count()
        + $journey['secondEnrollment']->progress()->count();
    $attemptRows = $journey['firstEnrollment']->quizAttempts()->count()
        + $journey['secondEnrollment']->quizAttempts()->count();
    $points = (int) $journey['firstEnrollment']->progress()->sum('points_awarded')
        + (int) $journey['secondEnrollment']->progress()->sum('points_awarded');

    $this->actingAs($journey['teacher'])
        ->get(route('teacher.tracking.show', [$journey['classroom'], $journey['assignment']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Tracking/Show')
            ->has('enrollments', 2)
            ->where('enrollments.0.student.name', 'Alpha Learner')
            ->missing('enrollments.0.student.email')
            ->where('enrollments.0.status', 'in_progress')
            ->where('enrollments.0.membership_active', false)
            ->where('enrollments.0.enrollment_active', false)
            ->where('enrollments.0.completed_nodes', 1)
            ->where('enrollments.0.total_nodes', 3)
            ->where('enrollments.0.progress_percent', 33)
            ->where('enrollments.0.points', 10)
            ->where('enrollments.0.attempt_count', 1)
            ->where('enrollments.0.best_score', 66.67)
            ->where('enrollments.1.student.name', 'Beta Learner')
            ->where('enrollments.1.status', 'completed')
            ->where('enrollments.1.completed_nodes', 3)
            ->where('enrollments.1.progress_percent', 100)
            ->where('enrollments.1.points', 30)
            ->where('enrollments.1.attempt_count', 2)
            ->where('enrollments.1.best_score', 100));

    expect($journey['firstEnrollment']->progress()->count() + $journey['secondEnrollment']->progress()->count())
        ->toBe($progressRows)
        ->and($journey['firstEnrollment']->quizAttempts()->count() + $journey['secondEnrollment']->quizAttempts()->count())
        ->toBe($attemptRows)
        ->and((int) $journey['firstEnrollment']->progress()->sum('points_awarded')
            + (int) $journey['secondEnrollment']->progress()->sum('points_awarded'))
        ->toBe($points);
});

test('individual tracking keeps a failed quiz pending and shows passed attempt history', function () {
    $journey = createTeacherTrackingJourney();
    prepareTeacherTrackingActivity($journey);

    $this->actingAs($journey['teacher'])
        ->get(route('teacher.tracking.enrollments.show', [
            $journey['classroom'],
            $journey['assignment'],
            $journey['firstEnrollment'],
        ]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Tracking/Enrollment')
            ->missing('enrollment.student.email')
            ->where('enrollment.status', 'in_progress')
            ->where('enrollment.nodes.1.type', 'quiz')
            ->where('enrollment.nodes.1.completed', false)
            ->where('enrollment.nodes.1.points', 0)
            ->where('enrollment.nodes.1.quiz.attempt_count', 1)
            ->where('enrollment.nodes.1.quiz.best_score', 66.67)
            ->where('enrollment.nodes.1.quiz.ever_passed', false)
            ->where('enrollment.nodes.1.quiz.attempts.0.passed', false));

    $this->actingAs($journey['teacher'])
        ->get(route('teacher.tracking.enrollments.show', [
            $journey['classroom'],
            $journey['assignment'],
            $journey['secondEnrollment'],
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('enrollment.status', 'completed')
            ->where('enrollment.nodes.1.completed', true)
            ->where('enrollment.nodes.1.quiz.attempt_count', 2)
            ->where('enrollment.nodes.1.quiz.best_score', 100)
            ->where('enrollment.nodes.1.quiz.ever_passed', true)
            ->has('enrollment.nodes.1.quiz.attempts', 2));
});

test('tracking lists only owned classes and rejects foreign or mismatched identifiers', function () {
    $journey = createTeacherTrackingJourney();
    $other = createTeacherTrackingJourney();
    $student = User::factory()->student()->create();

    $this->actingAs($journey['teacher'])
        ->get(route('teacher.tracking.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Tracking/Index')
            ->has('classrooms', 1)
            ->where('classrooms.0.id', $journey['classroom']->id)
            ->where('classrooms.0.assignments.0.id', $journey['assignment']->id)
            ->missing('classrooms.0.teacher_id')
            ->missing('classrooms.0.assignments.0.mission.teacher_id'));

    $this->actingAs($other['teacher'])
        ->get(route('teacher.tracking.show', [$journey['classroom'], $journey['assignment']]))
        ->assertForbidden();

    $this->actingAs($journey['teacher'])
        ->get(route('teacher.tracking.show', [$journey['classroom'], $other['assignment']]))
        ->assertNotFound();

    $this->actingAs($journey['teacher'])
        ->get(route('teacher.tracking.enrollments.show', [
            $journey['classroom'],
            $journey['assignment'],
            $other['firstEnrollment'],
        ]))
        ->assertNotFound();

    $this->actingAs($student)
        ->get(route('teacher.tracking.index'))
        ->assertForbidden();
});
