<?php

use App\Enums\UserRole;
use App\Models\AvatarProfile;
use App\Models\Classroom;
use App\Models\ClassroomMembership;
use App\Models\CoinLedgerEntry;
use App\Models\CosmeticItem;
use App\Models\MissionEnrollment;
use App\Models\MissionNode;
use App\Models\NodeProgress;
use App\Models\StudentCosmeticItem;
use App\Models\StudentRewardGrant;
use App\Models\User;
use App\Services\AvatarShopService;
use App\Services\StudentRewardService;
use Database\Seeders\CosmeticCatalogSeeder;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
    $this->seed(CosmeticCatalogSeeder::class);
});

/** @return array{student: User, profile: AvatarProfile, membership: ClassroomMembership} */
function createShopStudent(string $character = 'character-a', bool $activeMembership = true): array
{
    $student = User::factory()->create(['role' => UserRole::Student]);
    $profile = AvatarProfile::factory()->for($student, 'student')->create([
        'character_key' => $character,
    ]);
    app(AvatarShopService::class)->grantStarter($student, $profile);
    $classroom = Classroom::factory()->create();
    $membership = ClassroomMembership::factory()
        ->for($classroom)
        ->for($student, 'student')
        ->create(['active' => $activeMembership]);

    return compact('student', 'profile', 'membership');
}

function grantShopRewards(User $student, int $experience, int $coins): void
{
    $enrollment = MissionEnrollment::factory()->for($student, 'student')->create();
    $mission = $enrollment->assignment()->firstOrFail()->mission()->firstOrFail();
    $grantCount = max(1, (int) ceil($experience / StudentRewardService::EXPERIENCE_PER_ACTIVITY));
    $remainingExperience = $experience;

    foreach (range(1, $grantCount) as $position) {
        $node = MissionNode::factory()->for($mission)->create(['position' => $position]);
        $progress = NodeProgress::factory()
            ->for($enrollment, 'enrollment')
            ->for($node, 'node')
            ->create();
        $awarded = min(StudentRewardService::EXPERIENCE_PER_ACTIVITY, $remainingExperience);
        $grant = new StudentRewardGrant([
            'experience_awarded' => $awarded,
            'coins_awarded' => $position === 1 ? $coins : 0,
            'awarded_at' => now(),
        ]);
        $grant->student()->associate($student);
        $grant->node()->associate($node);
        $grant->firstProgress()->associate($progress);
        $grant->save();

        if ($position === 1 && $coins > 0) {
            $entry = new CoinLedgerEntry([
                'amount' => $coins,
                'reason' => CoinLedgerEntry::REASON_ACTIVITY_COMPLETION,
            ]);
            $entry->student()->associate($student);
            $entry->rewardGrant()->associate($grant);
            $entry->save();
        }

        $remainingExperience -= $awarded;
    }
}

test('the catalog seeder is idempotent and first access grants and equips only the starter appearance', function () {
    $this->seed(CosmeticCatalogSeeder::class);
    expect(CosmeticItem::query()->count())->toBe(6);

    $student = User::factory()->create(['role' => UserRole::Student]);

    $this->actingAs($student)
        ->post(route('student.avatar.setup.store'), ['character_key' => 'character-b'])
        ->assertSessionHasNoErrors();

    $profile = $student->avatarProfile()->with('equippedAppearance')->sole();

    expect($profile->equippedAppearance->sku)->toBe('character-b-inicial')
        ->and($student->cosmeticItems()->count())->toBe(1)
        ->and($student->coinLedgerEntries()->count())->toBe(0);

    $this->seed(CosmeticCatalogSeeder::class);

    expect(CosmeticItem::query()->count())->toBe(6)
        ->and($student->cosmeticItems()->count())->toBe(1)
        ->and($profile->refresh()->equipped_cosmetic_item_id)->toBe($profile->equippedAppearance->id);
});

test('a student buys an available appearance with an auditable debit without equipping it', function () {
    $journey = createShopStudent();
    grantShopRewards($journey['student'], 100, 6);
    $arcane = CosmeticItem::query()->where('sku', 'character-a-arcana')->sole();
    $initialEquipped = $journey['profile']->equipped_cosmetic_item_id;
    $progressBefore = NodeProgress::query()->count();
    $pointsBefore = (int) NodeProgress::query()->sum('points_awarded');

    $this->actingAs($journey['student'])
        ->post(route('student.avatar.catalog.purchase', $arcane))
        ->assertRedirect(route('student.avatar.index'))
        ->assertSessionHasNoErrors();

    expect($journey['student']->cosmeticItems()->where('cosmetic_item_id', $arcane->id)->count())->toBe(1)
        ->and(CoinLedgerEntry::query()->where('cosmetic_item_id', $arcane->id)->value('amount'))->toBe(-6)
        ->and($journey['profile']->refresh()->equipped_cosmetic_item_id)->toBe($initialEquipped)
        ->and(app(StudentRewardService::class)->summary($journey['student']))
        ->toBe(['experience' => 100, 'level' => 2, 'coins' => 0])
        ->and(NodeProgress::query()->count())->toBe($progressBefore)
        ->and((int) NodeProgress::query()->sum('points_awarded'))->toBe($pointsBefore);
});

test('insufficient coins or level reject purchase without creating ownership or debit', function (int $xp, int $coins, string $error) {
    $journey = createShopStudent();
    grantShopRewards($journey['student'], $xp, $coins);
    $arcane = CosmeticItem::query()->where('sku', 'character-a-arcana')->sole();

    $this->actingAs($journey['student'])
        ->post(route('student.avatar.catalog.purchase', $arcane))
        ->assertSessionHasErrors(['item' => $error]);

    $this->assertDatabaseMissing('student_cosmetic_items', [
        'student_id' => $journey['student']->id,
        'cosmetic_item_id' => $arcane->id,
    ]);
    $this->assertDatabaseMissing('coin_ledger_entries', [
        'student_id' => $journey['student']->id,
        'cosmetic_item_id' => $arcane->id,
    ]);
})->with([
    'coins' => [100, 5, 'No tienes monedas suficientes para esta apariencia.'],
    'level' => [90, 20, 'Necesitas alcanzar el nivel 2.'],
]);

test('a repeated purchase creates one ownership and one debit', function () {
    $journey = createShopStudent();
    grantShopRewards($journey['student'], 100, 12);
    $arcane = CosmeticItem::query()->where('sku', 'character-a-arcana')->sole();
    $route = route('student.avatar.catalog.purchase', $arcane);

    foreach (range(1, 2) as $attempt) {
        $this->actingAs($journey['student'])->post($route)->assertSessionHasNoErrors();
    }

    expect(StudentCosmeticItem::query()
        ->where('student_id', $journey['student']->id)
        ->where('cosmetic_item_id', $arcane->id)
        ->count())->toBe(1)
        ->and(CoinLedgerEntry::query()
            ->where('student_id', $journey['student']->id)
            ->where('cosmetic_item_id', $arcane->id)
            ->count())->toBe(1)
        ->and(app(StudentRewardService::class)->summary($journey['student'])['coins'])->toBe(6);
});

test('inactive membership inactive catalog and browser controlled values cannot bypass purchase rules', function () {
    $journey = createShopStudent(activeMembership: false);
    grantShopRewards($journey['student'], 100, 20);
    $arcane = CosmeticItem::query()->where('sku', 'character-a-arcana')->sole();

    $this->actingAs($journey['student'])
        ->post(route('student.avatar.catalog.purchase', $arcane))
        ->assertSessionHasErrors('item');

    $journey['membership']->update(['active' => true]);

    $this->actingAs($journey['student'])
        ->post(route('student.avatar.catalog.purchase', $arcane), [
            'coin_price' => 0,
            'minimum_level' => 1,
            'student_id' => $journey['student']->id,
        ])
        ->assertSessionHasErrors(['coin_price', 'minimum_level', 'student_id']);

    $arcane->update(['active' => false]);

    $this->actingAs($journey['student'])
        ->post(route('student.avatar.catalog.purchase', $arcane))
        ->assertForbidden();

    expect($journey['student']->cosmeticItems()->count())->toBe(1)
        ->and($journey['student']->coinLedgerEntries()->where('amount', '<', 0)->count())->toBe(0);
});

test('purchased appearances can be equipped without changing academic or reward totals', function () {
    $journey = createShopStudent();
    grantShopRewards($journey['student'], 100, 9);
    $arcane = CosmeticItem::query()->where('sku', 'character-a-arcana')->sole();
    $this->actingAs($journey['student'])->post(route('student.avatar.catalog.purchase', $arcane));
    $ownership = $journey['student']->cosmeticItems()->where('cosmetic_item_id', $arcane->id)->sole();
    $summary = app(StudentRewardService::class)->summary($journey['student']);
    $points = (int) NodeProgress::query()->sum('points_awarded');

    $this->actingAs($journey['student'])
        ->patch(route('student.avatar.inventory.equip', $ownership))
        ->assertRedirect(route('student.avatar.index'))
        ->assertSessionHasNoErrors();

    expect($journey['profile']->refresh()->equipped_cosmetic_item_id)->toBe($arcane->id)
        ->and(app(StudentRewardService::class)->summary($journey['student']))->toBe($summary)
        ->and((int) NodeProgress::query()->sum('points_awarded'))->toBe($points);
});

test('students cannot buy another character item or equip another students ownership', function () {
    $first = createShopStudent('character-a');
    $second = createShopStudent('character-b');
    grantShopRewards($first['student'], 100, 12);
    grantShopRewards($second['student'], 100, 12);
    $arcaneA = CosmeticItem::query()->where('sku', 'character-a-arcana')->sole();

    $this->actingAs($first['student'])->post(route('student.avatar.catalog.purchase', $arcaneA));
    $ownership = $first['student']->cosmeticItems()->where('cosmetic_item_id', $arcaneA->id)->sole();

    $this->actingAs($second['student'])
        ->post(route('student.avatar.catalog.purchase', $arcaneA))
        ->assertSessionHasErrors('item');
    $this->actingAs($second['student'])
        ->patch(route('student.avatar.inventory.equip', $ownership))
        ->assertForbidden();

    expect($second['profile']->refresh()->equippedAppearance()->value('sku'))->toBe('character-b-inicial');
});

test('only students receive the private catalog and its server calculated states', function () {
    $journey = createShopStudent();
    grantShopRewards($journey['student'], 100, 5);

    $this->actingAs($journey['student'])
        ->get(route('student.avatar.index'))
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Avatar/Index')
            ->where('rewards.level', 2)
            ->where('rewards.coins', 5)
            ->has('catalog', 3)
            ->where('catalog.0.status', 'Comprada')
            ->where('catalog.1.status', 'Sin monedas')
            ->where('catalog.2.status', 'Nivel insuficiente'));

    $teacher = User::factory()->teacher()->create();
    $this->actingAs($teacher)->get('/student/avatar')->assertForbidden();
});
