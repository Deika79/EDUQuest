<?php

use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\CoinLedgerEntry;
use App\Models\Mission;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\StudentRewardGrant;
use App\Models\User;
use App\Services\MissionAssignmentManager;
use App\Services\MissionLifecycle;
use App\Services\MissionNodeWriter;
use App\Services\StudentRewardService;
use Inertia\Testing\AssertableInertia as Assert;

use function Pest\Laravel\actingAs;

function rewardNodePayload(string $type, int $coins): array
{
    return match ($type) {
        'explanation' => [
            'title' => 'Read the reward briefing',
            'type' => 'explanation',
            'body' => 'A complete explanation.',
            'coin_reward' => $coins,
        ],
        'video' => [
            'title' => 'Review the reward video',
            'type' => 'video',
            'video_provider' => 'youtube',
            'video_reference' => 'dQw4w9WgXcQ',
            'coin_reward' => $coins,
        ],
        'quiz' => [
            'title' => 'Pass the reward quiz',
            'type' => 'quiz',
            'pass_threshold' => 70,
            'coin_reward' => $coins,
            'questions' => [[
                'statement' => 'Which answer is correct?',
                'explanation' => 'The first answer is correct.',
                'options' => [
                    ['text' => 'Correct', 'is_correct' => true],
                    ['text' => 'Incorrect', 'is_correct' => false],
                ],
            ]],
        ],
        'flashcards' => [
            'title' => 'Review the reward cards',
            'type' => 'flashcards',
            'coin_reward' => $coins,
            'flashcards' => [
                ['front' => 'XP', 'back' => 'Experience'],
                ['front' => 'Coin', 'back' => 'Spendable balance'],
            ],
        ],
    };
}

/**
 * @param  list<array{type: string, coins: int}>  $nodes
 * @return array{teacher: User, student: User, classroom: Classroom, membership: ClassroomMembership, mission: Mission, enrollment: MissionEnrollment}
 */
function createRewardJourney(array $nodes): array
{
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    $classroom = Classroom::factory()->for($teacher, 'teacher')->create();
    $membership = ClassroomMembership::factory()->for($classroom)->for($student, 'student')->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $writer = app(MissionNodeWriter::class);

    foreach ($nodes as $node) {
        $writer->save($mission, null, rewardNodePayload($node['type'], $node['coins']));
    }

    app(MissionLifecycle::class)->publish($mission);
    $assignment = app(MissionAssignmentManager::class)->assign($mission, collect([$classroom]))->firstOrFail();
    $enrollment = $assignment->enrollments()->where('student_id', $student->id)->firstOrFail();

    return compact('teacher', 'student', 'classroom', 'membership', 'mission', 'enrollment');
}

function completeRewardNode(User $student, MissionEnrollment $enrollment, MissionNode $node): void
{
    if ($node->type->value === 'quiz') {
        $question = $node->questions()->with('options')->firstOrFail();
        $option = $question->options->firstWhere('is_correct', true);

        actingAs($student)->post(
            route('student.missions.nodes.quiz-attempts.store', [$enrollment, $node]),
            ['answers' => [[
                'question_id' => $question->id,
                'option_id' => $option->id,
            ]]],
        )->assertSessionHasNoErrors();

        return;
    }

    $payload = ['confirmed' => true];
    if ($node->type->value === 'flashcards') {
        $payload['flashcard_ids'] = $node->flashcards()->pluck('id')->all();
    }

    actingAs($student)->post(
        route('student.missions.nodes.complete', [$enrollment, $node]),
        $payload,
    )->assertSessionHasNoErrors();
}

test('all activity types grant separate points experience and configured coins once', function () {
    $journey = createRewardJourney([
        ['type' => 'explanation', 'coins' => 1],
        ['type' => 'video', 'coins' => 2],
        ['type' => 'quiz', 'coins' => 3],
        ['type' => 'flashcards', 'coins' => 0],
    ]);

    expect($journey['enrollment']->assignment->nodeRewards()->count())->toBe(4)
        ->and((int) $journey['enrollment']->assignment->nodeRewards()->sum('experience_reward'))->toBe(40)
        ->and((int) $journey['enrollment']->assignment->nodeRewards()->sum('coin_reward'))->toBe(6);

    foreach ($journey['mission']->nodes()->get() as $node) {
        completeRewardNode($journey['student'], $journey['enrollment'], $node);
    }

    expect((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(40)
        ->and(StudentRewardGrant::query()->count())->toBe(4)
        ->and((int) StudentRewardGrant::query()->sum('experience_awarded'))->toBe(40)
        ->and((int) StudentRewardGrant::query()->sum('coins_awarded'))->toBe(6)
        ->and(CoinLedgerEntry::query()->count())->toBe(3)
        ->and((int) CoinLedgerEntry::query()->sum('amount'))->toBe(6);

    $this->actingAs($journey['student'])
        ->get(route('student.dashboard'))
        ->assertInertia(fn (Assert $page) => $page
            ->where('rewards.experience', 40)
            ->where('rewards.level', 1)
            ->where('rewards.coins', 6));
});

test('a repeated completion keeps one global reward and one ledger credit', function () {
    $journey = createRewardJourney([['type' => 'explanation', 'coins' => 3]]);
    $node = $journey['mission']->nodes()->firstOrFail();

    completeRewardNode($journey['student'], $journey['enrollment'], $node);
    completeRewardNode($journey['student'], $journey['enrollment'], $node);

    expect($journey['enrollment']->progress()->count())->toBe(1)
        ->and(StudentRewardGrant::query()->count())->toBe(1)
        ->and(CoinLedgerEntry::query()->count())->toBe(1)
        ->and((int) CoinLedgerEntry::query()->sum('amount'))->toBe(3);
});

test('the same node in a second assignment gives enrollment points but no second global reward', function () {
    $journey = createRewardJourney([['type' => 'explanation', 'coins' => 2]]);
    $secondClassroom = Classroom::factory()->for($journey['teacher'], 'teacher')->create();
    ClassroomMembership::factory()
        ->for($secondClassroom)
        ->for($journey['student'], 'student')
        ->create();
    $secondAssignment = app(MissionAssignmentManager::class)
        ->assign($journey['mission'], collect([$secondClassroom]))
        ->firstOrFail();
    $secondEnrollment = $secondAssignment->enrollments()->firstOrFail();
    $node = $journey['mission']->nodes()->firstOrFail();

    completeRewardNode($journey['student'], $journey['enrollment'], $node);
    completeRewardNode($journey['student'], $secondEnrollment, $node);

    expect($journey['enrollment']->progress()->count())->toBe(1)
        ->and($secondEnrollment->progress()->count())->toBe(1)
        ->and((int) $journey['student']->missionEnrollments()
            ->withSum('progress', 'points_awarded')
            ->get()
            ->sum('progress_sum_points_awarded'))->toBe(20)
        ->and(StudentRewardGrant::query()->count())->toBe(1)
        ->and((int) StudentRewardGrant::query()->sum('experience_awarded'))->toBe(10)
        ->and((int) CoinLedgerEntry::query()->sum('amount'))->toBe(2);
});

test('coin limits are validated per node and per mission', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.nodes.store', $mission), rewardNodePayload('explanation', 4))
        ->assertSessionHasErrors('coin_reward');

    foreach (range(1, 6) as $position) {
        app(MissionNodeWriter::class)->save(
            $mission,
            null,
            rewardNodePayload('explanation', 3),
        );
    }

    $this->actingAs($teacher)
        ->post(route('teacher.missions.nodes.store', $mission), rewardNodePayload('explanation', 3))
        ->assertSessionHasErrors('coin_reward');

    expect($mission->nodes()->count())->toBe(6)
        ->and((int) $mission->nodes()->sum('coin_reward'))->toBe(18);
});

test('assignment snapshots preserve the promised reward', function () {
    $journey = createRewardJourney([['type' => 'explanation', 'coins' => 2]]);
    $node = $journey['mission']->nodes()->firstOrFail();
    $node->forceFill(['coin_reward' => 0])->save();

    completeRewardNode($journey['student'], $journey['enrollment'], $node);

    expect((int) $journey['enrollment']->assignment->nodeRewards()->value('coin_reward'))->toBe(2)
        ->and((int) StudentRewardGrant::query()->value('coins_awarded'))->toBe(2)
        ->and((int) CoinLedgerEntry::query()->sum('amount'))->toBe(2);
});

test('an inactive membership blocks rewards and reinstatement preserves the balance', function () {
    $journey = createRewardJourney([['type' => 'explanation', 'coins' => 2]]);
    $node = $journey['mission']->nodes()->firstOrFail();

    $this->actingAs($journey['teacher'])
        ->patch(route('teacher.classrooms.memberships.update', [$journey['classroom'], $journey['membership']]), [
            'active' => false,
        ])
        ->assertSessionHasNoErrors();
    $this->actingAs($journey['student'])
        ->post(route('student.missions.nodes.complete', [$journey['enrollment'], $node]), ['confirmed' => true])
        ->assertForbidden();
    expect(StudentRewardGrant::query()->count())->toBe(0);

    $this->actingAs($journey['teacher'])
        ->patch(route('teacher.classrooms.memberships.update', [$journey['classroom'], $journey['membership']]), [
            'active' => true,
        ])
        ->assertSessionHasNoErrors();
    completeRewardNode($journey['student'], $journey['enrollment']->refresh(), $node);

    $this->actingAs($journey['teacher'])
        ->patch(route('teacher.classrooms.memberships.update', [$journey['classroom'], $journey['membership']]), [
            'active' => false,
        ])
        ->assertSessionHasNoErrors();

    expect(app(StudentRewardService::class)->summary($journey['student']))
        ->toBe(['experience' => 10, 'level' => 1, 'coins' => 2]);
});

test('a failed quiz and later retries do not duplicate its reward', function () {
    $journey = createRewardJourney([['type' => 'quiz', 'coins' => 3]]);
    $quiz = $journey['mission']->nodes()->firstOrFail();
    $question = $quiz->questions()->with('options')->firstOrFail();
    $correct = $question->options->firstWhere('is_correct', true);
    $incorrect = $question->options->firstWhere('is_correct', false);
    $route = route('student.missions.nodes.quiz-attempts.store', [$journey['enrollment'], $quiz]);

    $this->actingAs($journey['student'])->post($route, ['answers' => [[
        'question_id' => $question->id,
        'option_id' => $incorrect->id,
    ]]])->assertSessionHasNoErrors();
    expect(StudentRewardGrant::query()->count())->toBe(0);

    foreach ([$correct, $incorrect] as $option) {
        $this->actingAs($journey['student'])->post($route, ['answers' => [[
            'question_id' => $question->id,
            'option_id' => $option->id,
        ]]])->assertSessionHasNoErrors();
    }

    expect($journey['enrollment']->quizAttempts()->count())->toBe(3)
        ->and($journey['enrollment']->progress()->count())->toBe(1)
        ->and(StudentRewardGrant::query()->count())->toBe(1)
        ->and((int) CoinLedgerEntry::query()->sum('amount'))->toBe(3);
});

test('level is derived from accumulated experience and coin balance remains independent', function () {
    $nodes = collect(range(1, 10))
        ->map(fn (): array => ['type' => 'explanation', 'coins' => 1])
        ->all();
    $journey = createRewardJourney($nodes);

    foreach ($journey['mission']->nodes()->get() as $node) {
        completeRewardNode($journey['student'], $journey['enrollment'], $node);
    }

    expect(app(StudentRewardService::class)->summary($journey['student']))
        ->toBe(['experience' => 100, 'level' => 2, 'coins' => 10])
        ->and((int) $journey['enrollment']->progress()->sum('points_awarded'))->toBe(100);
});
