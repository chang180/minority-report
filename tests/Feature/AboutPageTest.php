<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('about page redirects guests to login', function () {
    $this->get('/about')
        ->assertRedirect(route('login'));
});

test('about page renders for authenticated users', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/about')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('About/Index')
        );
});

test('about page is accessible without email verification', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/about')
        ->assertOk()
        ->assertInertia(fn ($page) => $page
            ->component('About/Index')
        );
});
