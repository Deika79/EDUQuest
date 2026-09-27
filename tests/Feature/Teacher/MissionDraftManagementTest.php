<?php

use App\Enums\MissionNodeType;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Models\Mission;
use App\Models\MissionNode;
use App\Models\User;
use App\Services\MissionReadiness;
use Inertia\Testing\AssertableInertia as Assert;

function validNodePayload(string $type): array
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
            'video_reference' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
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
                ['front' => 'Numerator', 'back' => 'The top number in a fraction.'],
                ['front' => 'Denominator', 'back' => 'The bottom number in a fraction.'],
            ],
        ],
    };
}

test('teachers create and list only their own manual drafts', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    Mission::factory()->for($otherTeacher, 'teacher')->create(['title' => 'Other draft']);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.store'), [
            'title' => 'Fractions rescue',
            'description' => 'Recover the missing fraction pieces.',
            'subject' => 'Mathematics',
            'level' => '5 Primary',
            'teacher_id' => $otherTeacher->id,
            'status' => MissionStatus::Published->value,
            'source' => MissionSource::Ai->value,
        ])
        ->assertSessionHasNoErrors();

    $mission = Mission::where('title', 'Fractions rescue')->firstOrFail();

    expect($mission->teacher_id)->toBe($teacher->id)
        ->and($mission->status)->toBe(MissionStatus::Draft)
        ->and($mission->source)->toBe(MissionSource::Manual);

    $this->actingAs($teacher)
        ->get(route('teacher.missions.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Missions/Index')
            ->has('missions', 1)
            ->where('missions.0.id', $mission->id));
});

test('all four node types are saved and available when reopening a draft', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();

    foreach (MissionNodeType::cases() as $type) {
        $this->actingAs($teacher)
            ->post(route('teacher.missions.nodes.store', $mission), validNodePayload($type->value))
            ->assertSessionHasNoErrors()
            ->assertRedirect(route('teacher.missions.show', $mission));
    }

    $this->actingAs($teacher)
        ->get(route('teacher.missions.show', $mission))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Missions/Show')
            ->has('nodes', 4)
            ->where('nodes.0.type', 'explanation')
            ->where('nodes.1.video_reference', 'dQw4w9WgXcQ')
            ->has('nodes.2.questions', 1)
            ->has('nodes.2.questions.0.options', 2)
            ->has('nodes.3.flashcards', 2)
            ->where('readiness.ready', true));
});

test('a teacher can edit and remove a node from an owned draft', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $node = MissionNode::factory()->for($mission)->create();

    $payload = validNodePayload('explanation');
    $payload['body'] = 'Updated explanation text.';

    $this->actingAs($teacher)
        ->patch(route('teacher.missions.nodes.update', [$mission, $node]), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.missions.show', $mission));

    expect($node->refresh()->body)->toBe('Updated explanation text.');

    $this->actingAs($teacher)
        ->delete(route('teacher.missions.nodes.destroy', [$mission, $node]))
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.missions.show', $mission));

    $this->assertDatabaseMissing('mission_nodes', ['id' => $node->id]);
});

test('fields retained by the browser for other node types are ignored', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $payload = validNodePayload('explanation') + [
        'video_provider' => 'youtube',
        'video_reference' => '',
        'pass_threshold' => 70,
        'questions' => [[
            'statement' => '',
            'explanation' => '',
            'options' => [
                ['text' => '', 'is_correct' => true],
                ['text' => '', 'is_correct' => false],
            ],
        ]],
        'flashcards' => [['front' => '', 'back' => '']],
    ];

    $this->actingAs($teacher)
        ->post(route('teacher.missions.nodes.store', $mission), $payload)
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('teacher.missions.show', $mission));

    $node = $mission->nodes()->firstOrFail();

    expect($node->type)->toBe(MissionNodeType::Explanation)
        ->and($node->questions()->count())->toBe(0)
        ->and($node->flashcards()->count())->toBe(0)
        ->and($node->video_id)->toBeNull();
});

test('nodes can move up and down while retaining a unique sequence', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();
    $first = MissionNode::factory()->for($mission)->create(['position' => 1, 'title' => 'First']);
    $second = MissionNode::factory()->for($mission)->create(['position' => 2, 'title' => 'Second']);
    $third = MissionNode::factory()->for($mission)->create(['position' => 3, 'title' => 'Third']);

    $this->actingAs($teacher)
        ->patch(route('teacher.missions.nodes.move', [$mission, $third]), ['direction' => 'up'])
        ->assertSessionHasNoErrors();

    expect($first->refresh()->position)->toBe(1)
        ->and($third->refresh()->position)->toBe(2)
        ->and($second->refresh()->position)->toBe(3)
        ->and($mission->nodes()->pluck('position')->all())->toBe([1, 2, 3]);
});

test('incomplete node content is rejected with specific errors', function (array $payload, array $errors) {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.nodes.store', $mission), $payload)
        ->assertSessionHasErrors($errors);

    expect($mission->nodes()->count())->toBe(0);
})->with([
    'explanation needs body' => [
        ['title' => 'Incomplete', 'type' => 'explanation', 'body' => ''],
        ['body'],
    ],
    'video needs a permitted valid reference' => [
        [
            'title' => 'Invalid video',
            'type' => 'video',
            'video_provider' => 'youtube',
            'video_reference' => 'https://example.test/video',
        ],
        ['video_reference'],
    ],
    'quiz needs questions' => [
        ['title' => 'Empty quiz', 'type' => 'quiz', 'pass_threshold' => 70, 'questions' => []],
        ['questions'],
    ],
    'quiz needs exactly one correct option' => [
        [
            'title' => 'Ambiguous quiz',
            'type' => 'quiz',
            'pass_threshold' => 70,
            'questions' => [[
                'statement' => 'Question',
                'explanation' => 'Explanation',
                'options' => [
                    ['text' => 'A', 'is_correct' => true],
                    ['text' => 'B', 'is_correct' => true],
                ],
            ]],
        ],
        ['questions.0.options'],
    ],
    'flashcards need complete pairs' => [
        [
            'title' => 'Incomplete cards',
            'type' => 'flashcards',
            'flashcards' => [['front' => 'Term', 'back' => '']],
        ],
        ['flashcards.0.back'],
    ],
]);

test('draft readiness requires at least one complete node', function () {
    $mission = Mission::factory()->create();
    $readiness = app(MissionReadiness::class);

    expect($readiness->check($mission))->toMatchArray([
        'ready' => false,
        'errors' => ['Add at least one node.'],
    ]);

    MissionNode::factory()->for($mission)->create();

    expect($readiness->check($mission->fresh()))->toMatchArray([
        'ready' => true,
        'errors' => [],
    ]);
});

test('a teacher cannot view or modify another teachers mission or nodes', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    $ownMission = Mission::factory()->for($teacher, 'teacher')->create();
    $otherMission = Mission::factory()->for($otherTeacher, 'teacher')->create();
    $otherNode = MissionNode::factory()->for($otherMission)->create();

    $this->actingAs($teacher)
        ->get(route('teacher.missions.show', $otherMission))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->patch(route('teacher.missions.update', $otherMission), [
            'title' => 'Taken over',
            'description' => 'Not allowed',
            'subject' => 'Science',
            'level' => '5 Primary',
        ])
        ->assertForbidden();

    $this->actingAs($teacher)
        ->patch(
            route('teacher.missions.nodes.update', [$ownMission, $otherNode]),
            validNodePayload('explanation'),
        )
        ->assertForbidden();

    expect($otherMission->refresh()->title)->not->toBe('Taken over');
});

test('students have no access to mission draft routes', function () {
    $student = User::factory()->student()->create();
    $mission = Mission::factory()->create();

    $this->actingAs($student)
        ->get(route('teacher.missions.index'))
        ->assertForbidden();

    $this->actingAs($student)
        ->get(route('teacher.missions.show', $mission))
        ->assertForbidden();
});

test('non draft missions cannot be edited through the draft editor', function () {
    $teacher = User::factory()->teacher()->create();
    $mission = Mission::factory()->for($teacher, 'teacher')->create([
        'status' => MissionStatus::Published,
    ]);

    $this->actingAs($teacher)
        ->patch(route('teacher.missions.update', $mission), [
            'title' => 'Changed published mission',
            'description' => $mission->description,
            'subject' => $mission->subject,
            'level' => $mission->level,
        ])
        ->assertForbidden();
});
