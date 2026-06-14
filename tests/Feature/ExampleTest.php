<?php

test('home page renders the verification input page', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Verification/Index')
            ->has('fixtures')
            ->has('defaultFixtureId')
        );
});
