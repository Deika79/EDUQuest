<?php

use App\Enums\AiGenerationStatus;
use App\Enums\MissionMapTheme;
use App\Enums\MissionSource;
use App\Enums\MissionStatus;
use App\Models\AiGeneration;
use App\Models\Mission;
use App\Models\User;
use App\Services\MissionReadiness;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

function aiGenerationForm(array $overrides = []): array
{
    return [
        'request_token' => (string) Str::uuid(),
        'topic' => 'Las fracciones equivalentes',
        'subject' => 'Matematicas',
        'level' => '5 Primaria',
        'objectives' => 'Reconocer y comparar fracciones equivalentes.',
        'difficulty' => 'intermediate',
        'node_count' => 4,
        'instructions' => 'Usa ejemplos cotidianos y lenguaje claro.',
        ...$overrides,
    ];
}

function validAiMissionOutput(bool $withVideo = true): array
{
    $secondNode = $withVideo
        ? [
            'type' => 'video',
            'title' => 'Observa un ejemplo visual',
            'body' => null,
            'video_search_terms' => 'fracciones equivalentes primaria explicacion visual',
            'pass_threshold' => null,
            'questions' => [],
            'flashcards' => [],
        ]
        : [
            'type' => 'explanation',
            'title' => 'Compara dos fracciones',
            'body' => 'Multiplicar numerador y denominador por el mismo numero conserva el valor.',
            'video_search_terms' => null,
            'pass_threshold' => null,
            'questions' => [],
            'flashcards' => [],
        ];

    return [
        'title' => 'La ruta de las fracciones equivalentes',
        'description' => 'Recupera las piezas del mapa comparando fracciones que representan la misma cantidad.',
        'nodes' => [
            [
                'type' => 'explanation',
                'title' => 'Prepara la expedicion',
                'body' => 'Dos fracciones son equivalentes cuando representan la misma cantidad.',
                'video_search_terms' => null,
                'pass_threshold' => null,
                'questions' => [],
                'flashcards' => [],
            ],
            $secondNode,
            [
                'type' => 'quiz',
                'title' => 'Comprueba lo aprendido',
                'body' => null,
                'video_search_terms' => null,
                'pass_threshold' => 70,
                'questions' => [[
                    'statement' => 'Que fraccion equivale a 1/2?',
                    'explanation' => 'Multiplicar numerador y denominador por dos produce 2/4.',
                    'options' => [
                        ['text' => '2/4', 'is_correct' => true],
                        ['text' => '2/3', 'is_correct' => false],
                    ],
                ]],
                'flashcards' => [],
            ],
            [
                'type' => 'flashcards',
                'title' => 'Repasa los conceptos',
                'body' => null,
                'video_search_terms' => null,
                'pass_threshold' => null,
                'questions' => [],
                'flashcards' => [
                    ['front' => 'Fraccion equivalente', 'back' => 'Representa la misma cantidad que otra fraccion.'],
                    ['front' => 'Simplificar', 'back' => 'Dividir numerador y denominador por el mismo numero.'],
                ],
            ],
        ],
    ];
}

function openAiMissionResponse(array $content): array
{
    return [
        'id' => 'resp_test_eduquest',
        'status' => 'completed',
        'output' => [[
            'type' => 'message',
            'content' => [[
                'type' => 'output_text',
                'text' => json_encode($content, JSON_THROW_ON_ERROR),
            ]],
        ]],
        'usage' => [
            'input_tokens' => 420,
            'output_tokens' => 860,
        ],
    ];
}

beforeEach(function () {
    $this->withoutVite();

    config([
        'services.openai.api_key' => 'test-key-not-real',
        'services.openai.model' => 'gpt-5.4-mini',
        'services.openai.timeout' => 2,
        'services.openai.daily_limit' => 5,
        'services.openai.max_output_tokens' => 6000,
    ]);
});

test('a valid provider response creates one owned AI draft with validated content', function () {
    $teacher = User::factory()->teacher()->create();
    Http::fake(['*' => Http::response(openAiMissionResponse(validAiMissionOutput()), 200)]);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasNoErrors();

    $mission = Mission::query()->sole();
    $generation = AiGeneration::query()->sole();

    expect($mission->teacher_id)->toBe($teacher->id)
        ->and($mission->status)->toBe(MissionStatus::Draft)
        ->and($mission->source)->toBe(MissionSource::Ai)
        ->and($mission->map_theme)->toBe(MissionMapTheme::Fantasy)
        ->and($mission->nodes()->count())->toBe(4)
        ->and($mission->assignments()->count())->toBe(0)
        ->and($generation->status)->toBe(AiGenerationStatus::Completed)
        ->and($generation->mission_id)->toBe($mission->id)
        ->and($generation->input_tokens)->toBe(420)
        ->and($generation->output_tokens)->toBe(860);

    $video = $mission->nodes()->where('type', 'video')->firstOrFail();

    expect($video->video_provider)->toBeNull()
        ->and($video->video_id)->toBeNull()
        ->and($video->review_required)->toBeTrue()
        ->and($video->review_note)->toContain('Sugerencia de busqueda');

    $this->actingAs($teacher)
        ->patch(route('teacher.missions.update', $mission), [
            'title' => $mission->title,
            'description' => $mission->description,
            'subject' => $mission->subject,
            'level' => $mission->level,
            'map_theme' => MissionMapTheme::Science->value,
        ])
        ->assertSessionHasNoErrors();

    expect($mission->refresh()->map_theme)->toBe(MissionMapTheme::Science);

    Http::assertSent(function (Request $request) use ($teacher): bool {
        $payload = $request->data();
        $prompt = data_get($payload, 'input.1.content.0.text');

        return $request->url() === 'https://api.openai.com/v1/responses'
            && data_get($payload, 'model') === 'gpt-5.4-mini'
            && data_get($payload, 'store') === false
            && data_get($payload, 'text.format.strict') === true
            && is_string($prompt)
            && ! str_contains($prompt, $teacher->username)
            && ! str_contains($prompt, (string) $teacher->email);
    });
});

test('invalid provider structure is rejected before any mission is saved', function () {
    $teacher = User::factory()->teacher()->create();
    $invalid = validAiMissionOutput(false);
    $invalid['nodes'][2]['questions'][0]['options'][0]['is_correct'] = false;
    Http::fake(['*' => Http::response(openAiMissionResponse($invalid), 200)]);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasErrors('generation');

    $this->assertDatabaseCount('missions', 0);
    $this->assertDatabaseHas('ai_generations', [
        'teacher_id' => $teacher->id,
        'status' => AiGenerationStatus::Failed->value,
        'error_code' => 'invalid_output',
        'mission_id' => null,
    ]);
});

test('the configurable daily quota blocks the provider call', function () {
    $teacher = User::factory()->teacher()->create();
    config(['services.openai.daily_limit' => 1]);

    $generation = new AiGeneration([
        'request_token' => (string) Str::uuid(),
        'status' => AiGenerationStatus::Failed,
        'input_summary' => aiGenerationForm(),
        'error_code' => 'provider_unavailable',
    ]);
    $generation->teacher()->associate($teacher);
    $generation->save();
    Http::fake();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasErrors('generation');

    Http::assertNothingSent();
    $this->assertDatabaseCount('ai_generations', 1);
    $this->assertDatabaseCount('missions', 0);
});

test('a provider timeout is recorded without creating a draft', function () {
    $teacher = User::factory()->teacher()->create();
    Http::fake(fn () => throw new ConnectionException('simulated timeout'));

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasErrors('generation');

    $this->assertDatabaseHas('ai_generations', [
        'teacher_id' => $teacher->id,
        'status' => AiGenerationStatus::Failed->value,
        'error_code' => 'provider_timeout',
    ]);
    $this->assertDatabaseCount('missions', 0);
});

test('a provider error is presented as recoverable and stores no raw response', function () {
    $teacher = User::factory()->teacher()->create();
    Http::fake(['*' => Http::response(['error' => ['message' => 'internal detail']], 500)]);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasErrors('generation');

    $generation = AiGeneration::query()->sole();

    expect($generation->status)->toBe(AiGenerationStatus::Failed)
        ->and($generation->error_code)->toBe('provider_unavailable')
        ->and($generation->output_json)->toBeNull();
    $this->assertDatabaseCount('missions', 0);
});

test('the request token makes a repeated submission idempotent', function () {
    $teacher = User::factory()->teacher()->create();
    $form = aiGenerationForm();
    Http::fake(['*' => Http::response(openAiMissionResponse(validAiMissionOutput(false)), 200)]);

    $this->actingAs($teacher)->post(route('teacher.missions.generate.store'), $form);
    $this->actingAs($teacher)->post(route('teacher.missions.generate.store'), $form);

    $this->assertDatabaseCount('ai_generations', 1);
    $this->assertDatabaseCount('missions', 1);
    Http::assertSentCount(1);
});

test('an interrupted generation stops blocking the form after its timeout window', function () {
    $teacher = User::factory()->teacher()->create();
    $generation = new AiGeneration([
        'request_token' => (string) Str::uuid(),
        'status' => AiGenerationStatus::Processing,
        'input_summary' => aiGenerationForm(),
    ]);
    $generation->teacher()->associate($teacher);
    $generation->save();

    $this->travel(33)->seconds();

    $this->actingAs($teacher)
        ->get(route('teacher.missions.generate.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('provider.generationInProgress', false));

    Http::fake(['*' => Http::response(openAiMissionResponse(validAiMissionOutput(false)), 200)]);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasNoErrors();

    expect($generation->refresh()->status)->toBe(AiGenerationStatus::Failed)
        ->and($generation->error_code)->toBe('interrupted');
    $this->assertDatabaseCount('missions', 1);
});

test('AI content remains a draft until its owner confirms review and uses normal publishing', function () {
    $teacher = User::factory()->teacher()->create();
    $otherTeacher = User::factory()->teacher()->create();
    Http::fake(['*' => Http::response(openAiMissionResponse(validAiMissionOutput(false)), 200)]);

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasNoErrors();

    $mission = Mission::query()->sole();

    expect(app(MissionReadiness::class)->check($mission)['ready'])->toBeFalse();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.publish', $mission))
        ->assertSessionHasErrors('mission');

    $this->actingAs($otherTeacher)
        ->post(route('teacher.missions.ai-review', $mission))
        ->assertForbidden();

    $this->actingAs($otherTeacher)
        ->get(route('teacher.missions.show', $mission))
        ->assertForbidden();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.ai-review', $mission))
        ->assertSessionHasNoErrors();

    expect(app(MissionReadiness::class)->check($mission->fresh())['ready'])->toBeTrue();

    $explanation = $mission->nodes()->where('type', 'explanation')->firstOrFail();
    $this->actingAs($teacher)
        ->patch(route('teacher.missions.nodes.update', [$mission, $explanation]), [
            'title' => $explanation->title,
            'type' => 'explanation',
            'body' => $explanation->body.' Revision docente.',
        ])
        ->assertSessionHasNoErrors();

    expect($mission->aiGeneration()->firstOrFail()->reviewed_at)->toBeNull()
        ->and(app(MissionReadiness::class)->check($mission->fresh())['ready'])->toBeFalse();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.ai-review', $mission))
        ->assertSessionHasNoErrors();

    $this->actingAs($teacher)
        ->post(route('teacher.missions.publish', $mission))
        ->assertSessionHasNoErrors();

    expect($mission->refresh()->status)->toBe(MissionStatus::Published)
        ->and($mission->assignments()->count())->toBe(0);
});

test('missing configuration is visible and no role can bypass teacher access', function () {
    $teacher = User::factory()->teacher()->create();
    $student = User::factory()->student()->create();
    config(['services.openai.api_key' => null]);

    $this->actingAs($teacher)
        ->get(route('teacher.missions.generate.create'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('teacher/Missions/Generate')
            ->where('provider.configured', false)
            ->where('provider.dailyLimit', 5));

    $this->actingAs($teacher)
        ->post(route('teacher.missions.generate.store'), aiGenerationForm())
        ->assertSessionHasErrors('generation');

    $this->actingAs($student)
        ->get(route('teacher.missions.generate.create'))
        ->assertForbidden();

    $this->assertDatabaseCount('ai_generations', 0);
    $this->assertDatabaseCount('missions', 0);
});
