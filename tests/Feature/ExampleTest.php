<?php

use Inertia\Testing\AssertableInertia as Assert;

test('returns a successful response', function () {
    $response = $this->get(route('home'));

    $response
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Welcome'));
});

test('public landing keeps registration closed', function () {
    $this->get(route('home'))
        ->assertOk()
        ->assertDontSee('/register');
});
