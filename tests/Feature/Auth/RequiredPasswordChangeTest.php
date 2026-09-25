<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

test('temporary passwords must be changed before opening a role dashboard', function () {
    $user = User::factory()->teacher()->mustChangePassword()->create();

    $this->actingAs($user)
        ->get(route('teacher.dashboard'))
        ->assertRedirect(route('password.change.edit'));
});

test('a user can replace a temporary password', function () {
    $user = User::factory()->teacher()->mustChangePassword()->create();
    $newPassword = Str::password(24);

    $this->actingAs($user)
        ->put(route('password.change.update'), [
            'current_password' => 'password',
            'password' => $newPassword,
            'password_confirmation' => $newPassword,
        ])
        ->assertSessionHasNoErrors()
        ->assertRedirect(route('dashboard'));

    expect($user->refresh()->must_change_password)->toBeFalse()
        ->and(Hash::check($newPassword, $user->password))->toBeTrue();
});
