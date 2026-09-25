<?php

use App\Models\User;

test('an inactive account loses access with an open session', function () {
    $user = User::factory()->teacher()->create();

    $this->actingAs($user);

    $user->forceFill(['active' => false])->save();

    $this->get(route('teacher.dashboard'))
        ->assertRedirect(route('login'))
        ->assertSessionHasErrors('username');

    $this->assertGuest();
});
