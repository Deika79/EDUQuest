<?php

use App\Models\User;
use App\Support\AvatarOptions;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Inertia\Testing\AssertableInertia as Assert;

beforeEach(function () {
    $this->withoutVite();
});

test('a student without a profile is sent to avatar setup before missions', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.avatar.setup.edit'));

    $this->actingAs($student)
        ->get(route('student.missions.index'))
        ->assertRedirect(route('student.avatar.setup.edit'));

    $this->actingAs($student)
        ->get(route('student.avatar.setup.edit'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('student/Avatar/Setup')
            ->has('characters', 2)
            ->where('characters.0.key', 'character-a')
            ->where('characters.1.key', 'character-b')
            ->where('selection.character_key', 'character-a'));
});

test('an existing student can save one valid avatar and then open missions', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('student.avatar.setup.store'), ['character_key' => 'character-b'])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('student.dashboard'));

    $profile = $student->avatarProfile()->sole();

    expect($profile->character_key)->toBe('character-b')
        ->and($profile->setup_completed_at)->not->toBeNull();

    $this->actingAs($student)
        ->get(route('student.missions.index'))
        ->assertOk();
});

test('avatar keys and browser controlled fields are validated on the server', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('student.avatar.setup.store'), [
            'character_key' => '<script>alert(1)</script>',
            'skin_key' => 'arcane-skin',
            'level' => 3,
            'asset_path' => '/untrusted/avatar.svg',
            'image' => 'data:image/png;base64,not-accepted',
            'html' => '<img src=x>',
        ])
        ->assertSessionHasErrors([
            'character_key',
            'skin_key',
            'level',
            'asset_path',
            'image',
            'html',
        ]);

    $this->assertDatabaseCount('avatar_profiles', 0);
});

test('a manipulated student id cannot create or replace another profile', function () {
    $owner = User::factory()->student()->create();
    $attacker = User::factory()->create();
    $ownerProfile = $owner->avatarProfile()->sole();

    $this->actingAs($attacker)
        ->post(route('student.avatar.setup.store'), [
            ...AvatarOptions::defaultSelection(),
            'student_id' => $owner->id,
        ])
        ->assertSessionHasErrors('student_id');

    expect(Gate::forUser($attacker)->allows('update', $ownerProfile))->toBeFalse();
    $this->assertDatabaseMissing('avatar_profiles', ['student_id' => $attacker->id]);
    $this->assertDatabaseHas('avatar_profiles', [
        'id' => $ownerProfile->id,
        'student_id' => $owner->id,
        'character_key' => $ownerProfile->character_key,
    ]);
});

test('teachers and administrators cannot access avatar setup', function (string $role) {
    $user = User::factory()->{$role}()->create();

    $this->actingAs($user)
        ->get(route('student.avatar.setup.edit'))
        ->assertForbidden();

    $this->actingAs($user)
        ->post(route('student.avatar.setup.store'), AvatarOptions::defaultSelection())
        ->assertForbidden();

    $this->assertDatabaseMissing('avatar_profiles', ['student_id' => $user->id]);
})->with(['teacher', 'administrator']);

test('repeated setup submissions keep one profile and do not replace it', function () {
    $student = User::factory()->create();

    $this->actingAs($student)
        ->post(route('student.avatar.setup.store'), AvatarOptions::defaultSelection())
        ->assertSessionHasNoErrors();

    $this->actingAs($student)
        ->post(route('student.avatar.setup.store'), [
            'character_key' => 'character-b',
        ])
        ->assertSessionHasNoErrors();

    $this->assertDatabaseCount('avatar_profiles', 1);
    $this->assertDatabaseHas('avatar_profiles', [
        'student_id' => $student->id,
        'character_key' => 'character-a',
    ]);

    $this->actingAs($student)
        ->get(route('student.avatar.setup.edit'))
        ->assertRedirect(route('student.dashboard'));
});

test('required password change happens before avatar setup', function () {
    $student = User::factory()->mustChangePassword()->create();
    $newPassword = Str::password(24);

    $this->actingAs($student)
        ->get(route('student.avatar.setup.edit'))
        ->assertRedirect(route('password.change.edit'));

    $this->actingAs($student)
        ->get(route('student.missions.index'))
        ->assertRedirect(route('password.change.edit'));

    $this->actingAs($student)
        ->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($student->refresh()->must_change_password)->toBeFalse()
        ->and(Hash::check($newPassword, $student->password))->toBeTrue();

    $this->actingAs($student)
        ->get(route('dashboard'))
        ->assertRedirect(route('student.avatar.setup.edit'));
});

test('public registration remains unavailable', function () {
    $this->get('/register')->assertNotFound();
    $this->post('/register', [
        'name' => 'Public student',
        'username' => 'public_student',
        'password' => 'not-used',
    ])->assertNotFound();
});
