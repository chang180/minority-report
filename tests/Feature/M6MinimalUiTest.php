<?php

use App\Models\ProviderResponse;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('M6-A renders the question input page with demo fixtures', function () {
    $this->get('/demo')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verification/Index')
            ->where('defaultFixtureId', 'M6-F02')
            ->has('fixtures', 5)
            ->where('fixtures.1.expected_consensus', 'Majority')
            ->where('fixtures.1.expected_trust', 'Medium')
        );
});

test('M6-A submits a question through the fake workflow and renders results', function () {
    $response = $this->post('/demo/verifications', [
        'question' => 'Did the product launch date pass consensus verification?',
        'fixture_id' => 'M6-F02',
    ]);

    $verification = VerificationRequest::query()->latest('id')->firstOrFail();

    $response->assertRedirect(route('demo.verifications.show', $verification));

    expect($verification->question)->toBe('Did the product launch date pass consensus verification?')
        ->and($verification->metadata['fixture_id'])->toBe('M6-F02')
        ->and($verification->providerResponses()->count())->toBe(3)
        ->and($verification->consensusResult)->not->toBeNull()
        ->and($verification->consensusResult->consensus['status'])->toBe('Majority')
        ->and($verification->consensusResult->consensus['minority_provider'])->toBe('gemini')
        ->and($verification->final_trust)->toBe('Medium')
        ->and($verification->final_verdict)->toContain('Minority Opinion');

    $this->get(route('demo.verifications.show', $verification))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verification/Show')
            ->where('verification.id', $verification->id)
            ->where('verification.final_trust', 'Medium')
            ->where('verification.consensus_summary.status', 'Majority')
            ->where('consensusResult.consensus.status', 'Majority')
            ->where('consensusResult.verdict_report.metadata.has_minority_report', true)
            ->has('providerResponses', 3)
            ->where('providerResponses.2.provider', 'gemini')
            ->where('providerResponses.2.normalized.direct_answer', 'no')
        );
});

test('M6-A result page exposes provider failure and extracted summary comparison', function () {
    $this->post('/demo/verifications', [
        'question' => 'Can the system surface insufficient provider evidence?',
        'fixture_id' => 'M6-F10',
    ])->assertRedirect();

    $verification = VerificationRequest::query()->latest('id')->firstOrFail();
    $anthropic = ProviderResponse::query()
        ->whereBelongsTo($verification)
        ->where('provider', 'anthropic')
        ->firstOrFail();
    $gemini = ProviderResponse::query()
        ->whereBelongsTo($verification)
        ->where('provider', 'gemini')
        ->firstOrFail();

    expect($verification->consensusResult->consensus['status'])->toBe('Insufficient')
        ->and($verification->final_trust)->toBe('Unknown')
        ->and($anthropic->extraction_status)->toBe('invalid_json')
        ->and($gemini->provider_status)->toBe('failed_timeout');

    $this->get(route('demo.verifications.show', $verification))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Verification/Show')
            ->where('verification.final_trust', 'Unknown')
            ->where('consensusResult.consensus.status', 'Insufficient')
            ->has('providerResponses', 3)
            ->where('providerResponses.1.extraction_status', 'invalid_json')
            ->where('providerResponses.2.provider_status', 'failed_timeout')
        );
});

test('M6-A validates question submission', function () {
    $this->from('/demo')
        ->post('/demo/verifications', [
            'question' => 'short',
            'fixture_id' => 'missing-fixture',
        ])
        ->assertRedirect('/demo')
        ->assertSessionHasErrors([
            'question' => '問題必須至少 8 個字元。',
            'fixture_id' => '選取的示範範例無效。',
        ]);
});
