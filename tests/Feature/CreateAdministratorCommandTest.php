<?php

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Support\Str;

test('the first administrator is created interactively', function () {
    $username = 'initial.admin';
    $email = 'initial.admin@example.test';
    $password = Str::password(24);

    $this->artisan('eduquest:create-admin')
        ->expectsQuestion('Full name', 'Initial Administrator')
        ->expectsQuestion('Username', $username)
        ->expectsQuestion('Email address', $email)
        ->expectsQuestion('Password', $password)
        ->expectsQuestion('Confirm password', $password)
        ->expectsOutputToContain('Administrator created successfully.')
        ->assertSuccessful();

    $administrator = User::where('username', $username)->firstOrFail();

    expect($administrator->role)->toBe(UserRole::Administrator)
        ->and($administrator->active)->toBeTrue()
        ->and($administrator->must_change_password)->toBeFalse();
});
