<?php

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\Consensus\ConsensusWorkflow;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('guest is redirected from verification create', function () {
    $this->get('/verifications/create')->assertRedirect('/login');
});

test('authenticated user can view verification create page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/verifications/create')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Verification/Create'));
});

test('authenticated user can post verification and gets user_id assigned', function () {
    $user = User::factory()->create();

    $factory = Mockery::mock(ConfiguredLlmProviderFactory::class);
    $workflow = Mockery::mock(ConsensusWorkflow::class);

    $fakeVerification = VerificationRequest::create([
        'question' => 'Test question for M7B?',
        'metadata' => [],
    ]);

    $factory->shouldReceive('forUser')->with(Mockery::on(fn ($u) => $u->is($user)))->andReturn([]);
    $workflow->shouldReceive('run')->andReturn($fakeVerification);

    app()->instance(ConfiguredLlmProviderFactory::class, $factory);
    app()->instance(ConsensusWorkflow::class, $workflow);

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Test question for M7B?'])
        ->assertRedirect();

    $fakeVerification->refresh();
    expect($fakeVerification->user_id)->toBe($user->id)
        ->and($fakeVerification->metadata['source'] ?? null)->toBe('authenticated');
});

test('user can view their own verification', function () {
    $user = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'My verification',
    ]);

    $this->actingAs($user)
        ->get("/verifications/{$verification->id}")
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Verification/Show'));
});

test('user cannot view another user\'s verification', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();

    $verification = VerificationRequest::create([
        'user_id' => $owner->id,
        'question' => 'Private question',
    ]);

    $this->actingAs($attacker)
        ->get("/verifications/{$verification->id}")
        ->assertForbidden();
});

test('admin can view any verification', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $user = User::factory()->create();
    $verification = VerificationRequest::create([
        'user_id' => $user->id,
        'question' => 'Some verification',
    ]);

    $this->actingAs($admin)
        ->get("/verifications/{$verification->id}")
        ->assertOk();
});

test('user cannot view guest demo verification', function () {
    $user = User::factory()->create();
    $demoVerification = VerificationRequest::create([
        'user_id' => null,
        'question' => 'Guest demo question',
    ]);

    $this->actingAs($user)
        ->get("/verifications/{$demoVerification->id}")
        ->assertForbidden();
});

test('demo verification stores source=demo in metadata', function () {
    $verification = VerificationRequest::create([
        'question' => 'test',
        'metadata' => ['source' => 'demo', 'demo_mode' => 'fake_fixtures'],
    ]);

    expect($verification->metadata['source'])->toBe('demo')
        ->and($verification->metadata['demo_mode'])->toBe('fake_fixtures');
});

test('verification post requires minimum question length', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'short'])
        ->assertSessionHasErrors('question');
});
