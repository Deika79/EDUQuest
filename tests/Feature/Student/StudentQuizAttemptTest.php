<?php

use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\QuizAttempt;
use App\Models\User;
use App\Services\MissionAssignmentManager;
use App\Services\MissionLifecycle;
use App\Services\MissionNodeWriter;
use Illuminate\Http\RedirectResponse;
use Illuminate\Testing\TestResponse;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function quizAttemptPayload(): array
{
    return [
        'title' => 'Fractions check',
        'type' => 'quiz',
        'pass_threshold' => 70,
        'questions' => [
            [
                'statement' => 'Which fraction is one half?',
                'explanation' => 'One half has one part out of two equal parts.',
                'options' => [
                    ['text' => '1/2', 'is_correct' => true],
                    ['text' => '1/3', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Which fraction is one quarter?',
                'explanation' => 'One quarter has one part out of four equal parts.',
                'options' => [
                    ['text' => '1/4', 'is_correct' => true],
                    ['text' => '2/4', 'is_correct' => false],
                ],
            ],
            [
                'statement' => 'Which fraction equals one?',
                'explanation' => 'A fraction equals one when numerator and denominator match.',
                'options' => [
                    ['text' => '3/3', 'is_correct' => true],
                    ['text' => '2/3', 'is_correct' => false],
                ],
            ],
        ],
    ];
}

/**
 * @return array{teacher: User, student: User, classroom: Classroom, membership: ClassroomMembership, mission: Mission, enrollment: MissionEnrollment, quiz: MissionNode, next: MissionNode|null}
 */
function createQuizAttemptJourney(bool $withPredecessor = false, bool $withNext = true): array
{
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $membership = ClassroomMembership::factory()->for($classroom)->for($student, 'student')->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $writer = app(MissionNodeWriter::class);

    if ($withPredecessor) {
        $writer->save($mission, null, [
            'title' => 'Read first',
            'type' => 'explanation',
            'body' => 'This stage must be completed first.',
        ]);
    }

    $quiz = $writer->save($mission, null, quizAttemptPayload());
    $next = $withNext ? $writer->save($mission, null, [
        'title' => 'Continue the mission',
        'type' => 'explanation',
        'body' => 'Unlocked only after passing.',
    ]) : null;

    app(MissionLifecycle::class)->publish($mission);
    $assignment = app(MissionAssignmentManager::class)->assign($mission, collect([$classroom]))->firstOrFail();
    $enrollment = $assignment->enrollments()->where('student_id', $student->id)->firstOrFail();

    return compact('teacher', 'student', 'classroom', 'membership', 'mission', 'enrollment', 'quiz', 'next');
}

/** @return list<array{question_id: int, option_id: int}> */
function quizAttemptAnswers(MissionNode $quiz, int $correctAnswers): array
{
    return $quiz->questions()->with('options')->get()->values()->map(
        function ($question, int $index) use ($correctAnswers): array {
            $option = $question->options->firstWhere('is_correct', $index < $correctAnswers)
                ?? $question->options->firstOrFail();

            return ['question_id' => $question->id, 'option_id' => $option->id];
        },
    )->all();
}

/** @return TestResponse<RedirectResponse> */
function submitQuizAttempt(array $journey, array $answers): TestResponse
{
    return actingAs($journey['student'])->post(
        route('student.missions.nodes.quiz-attempts.store', [$journey['enrollment'], $journey['quiz']]),
        ['answers' => $answers],
    );
}

test('an available quiz exposes questions without solution indicators', function () {
    $journey = createQuizAttemptJourney(withNext: false);

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $journey['quiz']]))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Missions/Activity')
            ->has('node.quiz.questions', 3)
            ->has('node.quiz.questions.0.options', 2)
            ->missing('node.quiz.questions.0.options.0.is_correct')
            ->missing('node.quiz.questions.0.explanation')
            ->where('node.quiz.latest_feedback', null));
});

test('two correct answers out of three fail an unrounded seventy percent threshold', function () {
    $journey = createQuizAttemptJourney();

    submitQuizAttempt($journey, quizAttemptAnswers($journey['quiz'], 2))
        ->assertSessionHasNoErrors();

    $attempt = QuizAttempt::query()->sole();
    expect($attempt->score)->toBe('66.666667')
        ->and($attempt->correct_answers)->toBe(2)
        ->and($attempt->passed)->toBeFalse()
        ->and($attempt->answers()->count())->toBe(3)
        ->and($journey['enrollment']->progress()->count())->toBe(0)
        ->and($journey['enrollment']->refresh()->activity_started_at)->not->toBeNull();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $journey['quiz']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('node.quiz.latest_feedback.score', 66.67));

    $this->actingAs($journey['student'])
        ->get(route('student.missions.show', $journey['enrollment']))
        ->assertInertia(fn (Assert $page) => $page
            ->where('nodes.0.status', 'available')
            ->where('nodes.1.status', 'locked'));
});

test('a passed quiz unlocks progress and a later failure does not revoke it or its best score', function () {
    $journey = createQuizAttemptJourney();

    submitQuizAttempt($journey, quizAttemptAnswers($journey['quiz'], 3))->assertSessionHasNoErrors();
    submitQuizAttempt($journey, quizAttemptAnswers($journey['quiz'], 0))->assertSessionHasNoErrors();

    expect($journey['enrollment']->quizAttempts()->count())->toBe(2)
        ->and($journey['enrollment']->quizAttempts()->where('passed', true)->count())->toBe(1)
        ->and($journey['enrollment']->progress()->count())->toBe(1)
        ->and((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(10);

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $journey['quiz']]))
        ->assertInertia(fn (Assert $page) => $page
            ->where('node.completed', true)
            ->where('node.quiz.attempt_count', 2)
            ->where('node.quiz.best_score', 100)
            ->where('node.quiz.ever_passed', true)
            ->where('node.quiz.latest_feedback.passed', false)
            ->where('node.quiz.latest_feedback.answers.0.explanation', 'One half has one part out of two equal parts.'));

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $journey['next']]))
        ->assertOk();
});

test('manipulated questionnaire answers are rejected', function (string $manipulation) {
    $journey = createQuizAttemptJourney(withNext: false);
    $answers = quizAttemptAnswers($journey['quiz'], 3);

    if ($manipulation === 'omitted question') {
        array_pop($answers);
    }

    if ($manipulation === 'duplicate question') {
        $answers[1]['question_id'] = $answers[0]['question_id'];
    }

    if ($manipulation === 'foreign question') {
        $foreign = createQuizAttemptJourney(withNext: false);
        $answers[0]['question_id'] = $foreign['quiz']->questions()->firstOrFail()->id;
    }

    if ($manipulation === 'foreign option') {
        $foreign = createQuizAttemptJourney(withNext: false);
        $answers[0]['option_id'] = $foreign['quiz']->questions()->firstOrFail()->options()->firstOrFail()->id;
    }

    submitQuizAttempt($journey, $answers)->assertSessionHasErrors();

    expect($journey['enrollment']->quizAttempts()->count())->toBe(0)
        ->and($journey['enrollment']->progress()->count())->toBe(0);
})->with(['omitted question', 'duplicate question', 'foreign question', 'foreign option']);

test('a blocked quiz rejects direct viewing and submission', function () {
    $journey = createQuizAttemptJourney(withPredecessor: true, withNext: false);

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $journey['quiz']]))
        ->assertForbidden();
    submitQuizAttempt($journey, quizAttemptAnswers($journey['quiz'], 3))->assertForbidden();

    expect(QuizAttempt::query()->count())->toBe(0);
});

test('an inactive membership blocks questionnaire viewing and submission', function () {
    $journey = createQuizAttemptJourney(withNext: false);
    $journey['membership']->forceFill(['active' => false, 'deactivated_at' => now()])->save();
    $journey['enrollment']->forceFill(['active' => false, 'deactivated_at' => now()])->save();

    $this->actingAs($journey['student'])
        ->get(route('student.missions.nodes.show', [$journey['enrollment'], $journey['quiz']]))
        ->assertForbidden();
    submitQuizAttempt($journey, quizAttemptAnswers($journey['quiz'], 3))->assertForbidden();
});

test('repeated passing attempts award points only once', function () {
    $journey = createQuizAttemptJourney(withNext: false);
    $answers = quizAttemptAnswers($journey['quiz'], 3);

    submitQuizAttempt($journey, $answers)->assertSessionHasNoErrors();
    submitQuizAttempt($journey, $answers)->assertSessionHasNoErrors();

    expect($journey['enrollment']->quizAttempts()->count())->toBe(2)
        ->and($journey['enrollment']->progress()->count())->toBe(1)
        ->and((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(10);
});

test('questionnaire submissions are briefly rate limited', function () {
    $journey = createQuizAttemptJourney(withNext: false);
    $answers = quizAttemptAnswers($journey['quiz'], 0);

    foreach (range(1, 5) as $attempt) {
        submitQuizAttempt($journey, $answers)->assertSessionHasNoErrors();
    }

    submitQuizAttempt($journey, $answers)->assertTooManyRequests();
    expect($journey['enrollment']->quizAttempts()->count())->toBe(5);
});
