<?php

test('welcome page renders inertia', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Welcome')
            ->has('laravelVersion')
            ->has('phpVersion')
        );
});
