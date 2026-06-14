<?php

use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest is redirected from verification list', function () {
    $this->get('/verifications')->assertRedirect('/login');
});

test('unverified user is redirected from verification list', function () {
    $user = User::factory()->unverified()->create();

    $this->actingAs($user)
        ->get('/verifications')
        ->assertRedirect();
});

test('verified user can view their verification list', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/verifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verification/Index')
            ->has('verifications')
            ->has('verifications.data')
        );
});

test('user only sees their own verifications', function () {
    $user = User::factory()->create();
    $other = User::factory()->create();

    VerificationRequest::create(['user_id' => $user->id, 'question' => 'My question', 'processing_status' => 'completed']);
    VerificationRequest::create(['user_id' => $other->id, 'question' => 'Other question', 'processing_status' => 'completed']);

    $this->actingAs($user)
        ->get('/verifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verification/Index')
            ->where('verifications.total', 1)
            ->where('verifications.data.0.question', 'My question')
        );
});

test('admin can see all verifications', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();

    VerificationRequest::create(['user_id' => $admin->id, 'question' => 'Admin question', 'processing_status' => 'completed']);
    VerificationRequest::create(['user_id' => $user->id, 'question' => 'User question', 'processing_status' => 'completed']);

    $this->actingAs($admin)
        ->get('/verifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('verifications.total', 2)
        );
});

test('verification list is paginated at 15 per page', function () {
    $user = User::factory()->create();

    for ($i = 1; $i <= 16; $i++) {
        VerificationRequest::create(['user_id' => $user->id, 'question' => "Question {$i}", 'processing_status' => 'completed']);
    }

    $this->actingAs($user)
        ->get('/verifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('verifications.total', 16)
            ->where('verifications.per_page', 15)
            ->where('verifications.last_page', 2)
        );
});

test('verification list item contains processing_status field', function () {
    $user = User::factory()->create();
    VerificationRequest::create(['user_id' => $user->id, 'question' => 'Test question', 'processing_status' => 'pending']);

    $this->actingAs($user)
        ->get('/verifications')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('verifications.data.0.processing_status', 'pending')
        );
});
