<?php

use App\Enums\MissionAssignmentStatus;
use App\Enums\MissionStatus;
use App\Enums\UserRole;
use App\Models\Classroom;
use App\Models\CosmeticItem;
use App\Models\Mission;
use App\Models\MissionAssignment;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\NodeProgress;
use App\Models\User;
use App\Services\StudentRewardService;
use Illuminate\Routing\Middleware\ThrottleRequests;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\TestCase;

beforeEach(function () {
    $this->withoutVite();
    $this->demoPassword = Str::password(24);
    putenv("EDUQUEST_DEMO_STUDENT_PASSWORD={$this->demoPassword}");
});

afterEach(function () {
    putenv('EDUQUEST_DEMO_STUDENT_PASSWORD');
});

test('the local demo command creates a ready journey without fabricated progress or balances', function () {
    $teacher = User::factory()->teacher()->create([
        'name' => 'Carlos Diaz',
        'username' => 'carlinchis',
        'active' => true,
    ]);

    $this->artisan('eduquest:prepare-demo')->assertSuccessful();

    $student = User::query()->where('username', 'alumno_demo')->sole();
    $classroom = Classroom::query()->where('name', 'Clase demo EDUQuest')->sole();
    $mission = Mission::query()->where('title', 'Exploradores del sistema solar')->sole();
    $assignment = MissionAssignment::query()->where('mission_id', $mission->id)->sole();
    $enrollment = $assignment->enrollments()->where('student_id', $student->id)->sole();

    expect($classroom->teacher_id)->toBe($teacher->id)
        ->and($student->name)->toBe('Alumno Demo')
        ->and($student->role)->toBe(UserRole::Student)
        ->and($student->active)->toBeTrue()
        ->and($student->must_change_password)->toBeFalse()
        ->and(Hash::check($this->demoPassword, $student->password))->toBeTrue()
        ->and($student->avatarProfile()->exists())->toBeTrue()
        ->and($student->cosmeticItems()->where('acquisition_type', 'starter')->count())->toBe(1)
        ->and($student->cosmeticItems()->where('acquisition_type', 'purchase')->count())->toBe(0)
        ->and($mission->teacher_id)->toBe($teacher->id)
        ->and($mission->status)->toBe(MissionStatus::Published)
        ->and($mission->nodes()->get()->map(fn ($node) => $node->type->value)->all())
        ->toBe(['explanation', 'video', 'quiz', 'flashcards'])
        ->and((int) $mission->nodes()->sum('coin_reward'))->toBe(9)
        ->and($assignment->classroom_id)->toBe($classroom->id)
        ->and($assignment->status)->toBe(MissionAssignmentStatus::Open)
        ->and((int) $assignment->nodeRewards()->sum('coin_reward'))->toBe(9)
        ->and($enrollment->active)->toBeTrue()
        ->and($enrollment->progress()->count())->toBe(0)
        ->and($student->rewardGrants()->count())->toBe(0)
        ->and($student->coinLedgerEntries()->count())->toBe(0);

    expect(Mission::query()->where('teacher_id', $teacher->id)->count())->toBe(5)
        ->and(MissionNode::query()->whereIn('mission_id', Mission::query()->select('id')->where('teacher_id', $teacher->id))->count())->toBe(20)
        ->and(MissionAssignment::query()->count())->toBe(5)
        ->and($student->missionEnrollments()->count())->toBe(5)
        ->and((int) MissionAssignment::query()->withSum('nodeRewards', 'experience_reward')->get()->sum('node_rewards_sum_experience_reward'))->toBe(200)
        ->and((int) MissionAssignment::query()->withSum('nodeRewards', 'coin_reward')->get()->sum('node_rewards_sum_coin_reward'))->toBe(45);

    $this->post('/login', [
        'username' => 'alumno_demo',
        'password' => $this->demoPassword,
    ])->assertRedirect(route('dashboard'));

    $this->actingAs($student)
        ->get(route('student.missions.index'))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('student/Dashboard')
            ->has('missions', 5)
            ->where('missions', fn ($missions): bool => collect($missions)
                ->pluck('title')
                ->contains('Exploradores del sistema solar')));

    $this->actingAs($teacher)
        ->get(route('teacher.classrooms.show', $classroom))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('teacher/Classrooms/Show')
            ->where('classroom.name', 'Clase demo EDUQuest'));

    $this->actingAs($teacher)
        ->get(route('teacher.missions.show', $mission))
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('teacher/Missions/Show')
            ->where('mission.title', 'Exploradores del sistema solar'));
});

test('running the demo command again preserves all journeys progress and password', function () {
    User::factory()->teacher()->create([
        'username' => 'carlinchis',
        'active' => true,
    ]);

    $this->artisan('eduquest:prepare-demo')->assertSuccessful();
    $student = User::query()->where('username', 'alumno_demo')->sole();
    $originalHash = $student->password;
    $enrollment = $student->missionEnrollments()->oldest('id')->firstOrFail();
    $firstNode = $enrollment->assignment->mission->nodes()->oldest('position')->firstOrFail();

    $this->actingAs($student)
        ->post(route('student.missions.nodes.complete', [$enrollment, $firstNode]), ['confirmed' => true])
        ->assertSessionHasNoErrors();
    $summaryBefore = app(StudentRewardService::class)->summary($student);
    $equippedBefore = $student->avatarProfile()->value('equipped_cosmetic_item_id');

    putenv('EDUQUEST_DEMO_STUDENT_PASSWORD='.Str::password(24));
    $this->artisan('eduquest:prepare-demo')->assertSuccessful();

    expect(User::query()->where('username', 'alumno_demo')->count())->toBe(1)
        ->and(Classroom::query()->where('name', 'Clase demo EDUQuest')->count())->toBe(1)
        ->and(Mission::query()->whereIn('title', [
            'Exploradores del sistema solar',
            'Guardianes del ciclo del agua',
            'Detectives de los ecosistemas',
            'Viaje al interior de la Tierra',
            'Laboratorio de la materia',
        ])->count())->toBe(5)
        ->and(MissionNode::query()->count())->toBe(20)
        ->and(MissionAssignment::query()->count())->toBe(5)
        ->and($student->refresh()->password)->toBe($originalHash)
        ->and($student->missionEnrollments()->count())->toBe(5)
        ->and(NodeProgress::query()->where('enrollment_id', $enrollment->id)->count())->toBe(1)
        ->and(app(StudentRewardService::class)->summary($student))->toBe($summaryBefore)
        ->and($student->avatarProfile()->value('equipped_cosmetic_item_id'))->toBe($equippedBefore);
});

test('an isolated demo student reaches level three and equips both earned appearances through real routes', function () {
    $this->withoutMiddleware(ThrottleRequests::class);

    User::factory()->teacher()->create([
        'username' => 'carlinchis',
        'active' => true,
    ]);

    $this->artisan('eduquest:prepare-demo')->assertSuccessful();
    $student = User::query()->where('username', 'alumno_demo')->sole();
    $enrollments = $student->missionEnrollments()
        ->with('assignment.mission.nodes.questions.options', 'assignment.mission.nodes.flashcards')
        ->oldest('id')
        ->get();
    $completed = 0;

    foreach ($enrollments as $enrollment) {
        foreach ($enrollment->assignment->mission->nodes->sortBy('position') as $node) {
            completePreparedDemoNode($this, $student, $enrollment, $node);
            $completed++;

            if ($completed === 10) {
                expect(app(StudentRewardService::class)->summary($student->refresh())['level'])->toBe(2)
                    ->and(app(StudentRewardService::class)->summary($student)['coins'])->toBeGreaterThanOrEqual(6);
                purchaseAndEquipPreparedDemoAppearance($this, $student, 'character-a-arcana');
                assertPreparedDemoAvatarPersists($this, $student, '/brand/avatars/assets/personaje-a-nivel-2-arcano.webp');
            }
        }
    }

    expect($completed)->toBe(20)
        ->and(app(StudentRewardService::class)->summary($student->refresh())['experience'])->toBe(200)
        ->and(app(StudentRewardService::class)->summary($student)['level'])->toBe(3)
        ->and(app(StudentRewardService::class)->summary($student)['coins'])->toBeGreaterThanOrEqual(12);

    purchaseAndEquipPreparedDemoAppearance($this, $student, 'character-a-espacial');
    assertPreparedDemoAvatarPersists($this, $student, '/brand/avatars/assets/personaje-a-nivel-3-espacial.webp');

    expect($student->cosmeticItems()->where('acquisition_type', 'purchase')->count())->toBe(2)
        ->and(app(StudentRewardService::class)->summary($student->refresh()))
        ->toBe(['experience' => 200, 'level' => 3, 'coins' => 27]);
});

function completePreparedDemoNode(
    TestCase $test,
    User $student,
    MissionEnrollment $enrollment,
    MissionNode $node,
): void {
    if ($node->type->value === 'quiz') {
        $answers = $node->questions->map(fn ($question): array => [
            'question_id' => $question->id,
            'option_id' => $question->options->firstWhere('is_correct', true)->id,
        ])->all();
        $test->actingAs($student)
            ->post(route('student.missions.nodes.quiz-attempts.store', [$enrollment, $node]), compact('answers'))
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        return;
    }

    $payload = ['confirmed' => true];
    if ($node->type->value === 'flashcards') {
        $payload['flashcard_ids'] = $node->flashcards->pluck('id')->all();
    }

    $test->actingAs($student)
        ->post(route('student.missions.nodes.complete', [$enrollment, $node]), $payload)
        ->assertRedirect()
        ->assertSessionHasNoErrors();
}

function purchaseAndEquipPreparedDemoAppearance(
    TestCase $test,
    User $student,
    string $sku,
): void {
    $item = CosmeticItem::query()->where('sku', $sku)->sole();
    $test->actingAs($student)
        ->post(route('student.avatar.catalog.purchase', $item))
        ->assertSessionHasNoErrors();
    $ownership = $student->cosmeticItems()->where('cosmetic_item_id', $item->id)->sole();
    $test->actingAs($student)
        ->patch(route('student.avatar.inventory.equip', $ownership))
        ->assertSessionHasNoErrors();
}

function assertPreparedDemoAvatarPersists(
    TestCase $test,
    User $student,
    string $expectedImage,
): void {
    foreach (range(1, 2) as $request) {
        $test->actingAs($student)
            ->get(route('student.dashboard'))
            ->assertOk()
            ->assertInertia(fn (Assert $page) => $page
                ->where('auth.user.avatar', $expectedImage)
                ->where('avatar.image', $expectedImage));
    }
}
