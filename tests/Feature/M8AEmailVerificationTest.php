<?php

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unverified user is redirected from verified routes', function () {
    $user = User::factory()->unverified()->create();

    $routes = [
        '/verifications',
        '/verifications/create',
    ];

    foreach ($routes as $route) {
        $this->actingAs($user)
            ->get($route)
            ->assertRedirect();
    }
});

test('unverified user cannot POST to verifications', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Is PHP 9 out yet?'])
        ->assertRedirect();

    expect(VerificationRequest::count())->toBe(0);
});

test('verified user can access verification routes', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/verifications')
        ->assertOk();

    $this->actingAs($user)
        ->get('/verifications/create')
        ->assertOk();
});

test('verified user (factory default) can post verifications', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Is PHP 9 released already?'])
        ->assertRedirect();

    expect(VerificationRequest::where('user_id', $user->id)->count())->toBeGreaterThanOrEqual(1);
});

test('auto verify on login marks previously unverified user as verified in testing', function () {
    $user = User::factory()->unverified()->create([
        'email' => 'login-auto@example.com',
        'password' => bcrypt('password123'),
    ]);

    $this->post('/login', [
        'email' => 'login-auto@example.com',
        'password' => 'password123',
    ])->assertRedirect();

    expect($user->fresh()->hasVerifiedEmail())->toBeTrue();
});

test('demo routes are not protected by verified middleware', function () {
    $this->get('/demo')->assertOk();
});
