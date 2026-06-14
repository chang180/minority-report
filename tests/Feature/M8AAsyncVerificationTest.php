<?php

use App\Jobs\RunAuthenticatedVerificationJob;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;

uses(RefreshDatabase::class);

test('POST /verifications creates pending record and dispatches job', function () {
    Bus::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Is PHP 9 released yet?'])
        ->assertRedirect();

    $verification = VerificationRequest::where('user_id', $user->id)->first();
    expect($verification)->not->toBeNull()
        ->and($verification->processing_status)->toBe('pending')
        ->and($verification->question)->toBe('Is PHP 9 released yet?');

    Bus::assertDispatched(RunAuthenticatedVerificationJob::class, function ($job) use ($user, $verification) {
        return $job->verificationRequestId === $verification->id
            && $job->userId === $user->id;
    });
});

test('POST /verifications redirects to show page', function () {
    Bus::fake();

    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Is PHP 9 released yet?'])
        ->assertRedirect();

    $verification = VerificationRequest::where('user_id', $user->id)->first();
    expect($verification)->not->toBeNull();
});

test('job sets processing_status to completed on success', function () {
    $user = User::factory()->create();

    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Test question for job?',
        'processing_status' => 'pending',
        'metadata' => ['source' => 'authenticated'],
    ]);

    $job = new RunAuthenticatedVerificationJob($verification->id, $user->id);

    // Workflow with disabled providers completes without real LLM calls.
    app()->call([$job, 'handle']);

    $verification->refresh();
    expect($verification->processing_status)->toBe('completed');
});

test('job sets processing_status to failed when exception occurs', function () {
    $user = User::factory()->create();

    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Test question for failure?',
        'processing_status' => 'pending',
        'metadata' => ['source' => 'authenticated'],
    ]);

    $job = new RunAuthenticatedVerificationJob($verification->id, $user->id);
    $job->failed(new RuntimeException('Simulated failure'));

    $verification->refresh();
    expect($verification->processing_status)->toBe('failed')
        ->and($verification->metadata['processing_error'])->toBe('Simulated failure');
});

test('GET /verifications/{id}/status returns JSON with processing_status', function () {
    $user = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Status check question?',
        'processing_status' => 'running',
    ]);

    $this->actingAs($user)
        ->getJson("/verifications/{$verification->id}/status")
        ->assertOk()
        ->assertJsonStructure(['id', 'processing_status', 'processing_error', 'final_trust', 'final_verdict', 'updated_at'])
        ->assertJsonFragment([
            'id' => $verification->id,
            'processing_status' => 'running',
        ]);
});

test('GET status returns processing_error for failed verification', function () {
    $user = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Failed question?',
        'processing_status' => 'failed',
        'metadata' => ['source' => 'authenticated', 'processing_error' => 'Something went wrong'],
    ]);

    $this->actingAs($user)
        ->getJson("/verifications/{$verification->id}/status")
        ->assertOk()
        ->assertJsonFragment([
            'processing_status' => 'failed',
            'processing_error' => 'Something went wrong',
        ]);
});

test('guest cannot access status endpoint', function () {
    $user = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Guest question?',
        'processing_status' => 'pending',
    ]);

    $this->getJson("/verifications/{$verification->id}/status")
        ->assertUnauthorized();
});

test('user cannot access another user status', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $owner->id,
        'question' => 'Private question?',
        'processing_status' => 'pending',
    ]);

    $this->actingAs($attacker)
        ->getJson("/verifications/{$verification->id}/status")
        ->assertForbidden();
});
