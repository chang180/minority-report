<?php

test('home page renders the product welcome page', function () {
    $this->get('/')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('Home/Welcome')
        );
});
