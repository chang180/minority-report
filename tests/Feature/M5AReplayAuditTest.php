<?php

use App\Consensus\ConsensusWorkflow;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderTimeoutException;
use App\Consensus\Fake\InMemoryFakeProviderRegistry;
use App\Consensus\Replay\ConsensusReplayService;
use App\Models\ConsensusResult;
use App\Models\ProviderResponse as ProviderResponseModel;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('M5-A replays a persisted request and reproduces the original verdict chain', function () {
    $original = m5aRunFixture('M5A-FULL', [
        m5aSuccess('openai', 'yes', [m5aClaim('date', 'launch date', '2024-03-15')]),
        m5aSuccess('anthropic', 'yes', [m5aClaim('date', 'launch date', '2024-03-15')]),
        m5aSuccess('gemini', 'no', [m5aClaim('date', 'launch date', '2024-03-15')]),
    ]);

    $replayed = app(ConsensusReplayService::class)->replayRequest($original->id);

    expect($replayed->id)->not->toBe($original->id)
        ->and($replayed->question)->toBe($original->question)
        ->and($replayed->metadata['replay']['source_request_id'])->toBe($original->id)
        ->and($replayed->providerResponses)->toHaveCount(3)
        ->and($replayed->consensusResult->alignment)->toBe($original->consensusResult->alignment)
        ->and($replayed->consensusResult->conflict_detection)->toBe($original->consensusResult->conflict_detection)
        ->and($replayed->consensusResult->consensus)->toBe($original->consensusResult->consensus)
        ->and($replayed->consensusResult->decision_key)->toBe($original->consensusResult->decision_key)
        ->and($replayed->consensusResult->decision_basis)->toBe($original->consensusResult->decision_basis)
        ->and($replayed->consensusResult->trust_base)->toBe($original->consensusResult->trust_base)
        ->and($replayed->consensusResult->applied_caps)->toBe($original->consensusResult->applied_caps)
        ->and($replayed->consensusResult->trust_level)->toBe($original->consensusResult->trust_level)
        ->and($replayed->consensusResult->verdict_report)->toBe($original->consensusResult->verdict_report)
        ->and($replayed->final_trust)->toBe($original->final_trust)
        ->and($replayed->final_verdict)->toBe($original->final_verdict);

    $original->providerResponses->each(function (ProviderResponseModel $source) use ($replayed): void {
        $copy = $replayed->providerResponses->firstWhere('provider', $source->provider);

        expect($copy)->not->toBeNull()
            ->and($copy->provider_prompt)->toBe($source->provider_prompt)
            ->and($copy->raw_answer)->toBe($source->raw_answer)
            ->and($copy->provider_status)->toBe($source->provider_status)
            ->and($copy->extraction_prompt)->toBe($source->extraction_prompt)
            ->and($copy->extractor_model)->toBe($source->extractor_model)
            ->and($copy->extraction_status)->toBe($source->extraction_status)
            ->and($copy->normalized)->toBe($source->normalized);
    });

    expect(VerificationRequest::count())->toBe(2)
        ->and(ProviderResponseModel::count())->toBe(6)
        ->and(ConsensusResult::count())->toBe(2);
});

test('M5-A replays the latest request for a fixture id', function () {
    $older = m5aRunFixture('M5A-FIXTURE', [
        m5aSuccess('openai', 'yes'),
        m5aSuccess('anthropic', 'yes'),
        m5aSuccess('gemini', 'yes'),
    ]);

    $latest = m5aRunFixture('M5A-FIXTURE', [
        m5aSuccess('openai', 'yes'),
        m5aSuccess('anthropic', 'yes'),
        m5aTimeout('gemini'),
    ]);

    $replayed = app(ConsensusReplayService::class)->replayFixture('M5A-FIXTURE');

    expect($replayed->metadata['replay']['source_request_id'])->toBe($latest->id)
        ->and($replayed->metadata['replay']['source_request_id'])->not->toBe($older->id)
        ->and($replayed->consensusResult->consensus)->toBe($latest->consensusResult->consensus)
        ->and($replayed->consensusResult->applied_caps)->toBe($latest->consensusResult->applied_caps);
});

test('M5-A restores a complete audit trail from the database', function () {
    $request = m5aRunFixture('M5A-AUDIT', [
        m5aSuccess('openai', 'yes', [m5aClaim('boolean', 'package maintained', 'true')]),
        m5aInvalidJson('anthropic'),
        m5aTimeout('gemini'),
    ]);

    $audit = app(ConsensusReplayService::class)->auditTrailForRequest($request->id);

    expect($audit['request']['question'])->toBe('Fixture M5A-AUDIT question')
        ->and($audit['request']['created_at'])->not->toBeNull()
        ->and($audit['classification'])->toMatchArray([
            'classified_type' => 'B',
            'classifier_confidence' => 'high',
            'answer_shape' => 'discrete',
            'requires_grounding' => false,
            'grounding_available' => false,
        ])
        ->and($audit['providers'])->toHaveCount(3);

    $openai = collect($audit['providers'])->firstWhere('provider', 'openai');
    $anthropic = collect($audit['providers'])->firstWhere('provider', 'anthropic');
    $gemini = collect($audit['providers'])->firstWhere('provider', 'gemini');

    expect($openai['provider_prompt'])->toContain('Expected answer shape: discrete')
        ->and($openai['raw_answer'])->not->toBeNull()
        ->and($openai['provider_status'])->toBe('success')
        ->and($openai['extraction_prompt'])->toContain('Do not use any other provider answer.')
        ->and($openai['extractor_model'])->toBe('fixture-json-replay')
        ->and($openai['extraction_status'])->toBe('success')
        ->and($openai['normalized']['claims'][0]['canonical_key'])->toBe('package maintained')
        ->and($openai['created_at'])->not->toBeNull()
        ->and($anthropic['provider_status'])->toBe('success')
        ->and($anthropic['extraction_status'])->toBe('invalid_json')
        ->and($anthropic['error']['message'])->toBe('Extractor JSON could not be decoded.')
        ->and($gemini['provider_status'])->toBe('failed_timeout')
        ->and($gemini['extraction_status'])->toBe('not_started');

    expect($audit['consensus_result']['alignment'])->toHaveKeys(['aligned', 'unmatched', 'unalignable', 'metadata'])
        ->and($audit['consensus_result']['conflict_detection'])->toBeArray()
        ->and($audit['consensus_result']['consensus']['status'])->toBe('Insufficient')
        ->and($audit['consensus_result']['decision_key'])->toBe('direct_answer')
        ->and($audit['consensus_result']['decision_basis']['effective_direct_answer_vote_count'])->toBe(1)
        ->and($audit['consensus_result']['trust_base'])->toBe('Unknown')
        ->and($audit['consensus_result']['applied_caps'])->toContain([
            'condition' => 'consensus_insufficient_or_failure',
            'cap' => 'Unknown',
        ])
        ->and($audit['consensus_result']['trust_level'])->toBe('Unknown')
        ->and($audit['consensus_result']['verdict_report']['summary'])->toBe('Single Provider Answer - Unverified.')
        ->and($audit['consensus_result']['errors'])->toHaveCount(2)
        ->and($audit['consensus_result']['created_at'])->not->toBeNull();
});

/**
 * @param  array<int, array<string, mixed>>  $providers
 */
function m5aRunFixture(string $fixtureId, array $providers): VerificationRequest
{
    $registry = new InMemoryFakeProviderRegistry;
    $llmProviders = [];

    foreach ($providers as $spec) {
        $registry->register($spec['provider'], m5aProviderBehavior($spec));
        $llmProviders[] = $registry->create($spec['provider']);
    }

    return app(ConsensusWorkflow::class)->run(new Question(
        text: "Fixture {$fixtureId} question",
        metadata: [
            'classification' => [
                'type' => 'B',
                'answer_shape' => 'discrete',
                'requires_grounding' => false,
                'classifier_confidence' => 'high',
            ],
            'fixture_id' => $fixtureId,
            'grounding_available' => false,
        ],
    ), $llmProviders);
}

/**
 * @param  array<int, array<string, mixed>>  $claims
 * @return array<string, mixed>
 */
function m5aSuccess(string $provider, string $directAnswer, array $claims = []): array
{
    return [
        'provider' => $provider,
        'mode' => 'success',
        'direct_answer' => $directAnswer,
        'claims' => $claims,
        'summary' => "{$provider} fixture answer",
    ];
}

/**
 * @return array<string, mixed>
 */
function m5aInvalidJson(string $provider): array
{
    return ['provider' => $provider, 'mode' => 'invalid_json'];
}

/**
 * @return array<string, mixed>
 */
function m5aTimeout(string $provider): array
{
    return ['provider' => $provider, 'mode' => 'timeout'];
}

/**
 * @return array<string, mixed>
 */
function m5aClaim(string $type, string $key, string $value, ?string $unit = null): array
{
    return [
        'type' => $type,
        'canonical_key' => $key,
        'subject' => $key,
        'predicate' => 'is',
        'value' => $value,
        'unit' => $unit,
        'source' => null,
    ];
}

/**
 * @param  array<string, mixed>  $spec
 */
function m5aProviderBehavior(array $spec): Closure
{
    return function () use ($spec): ProviderResponse {
        if ($spec['mode'] === 'timeout') {
            throw new ProviderTimeoutException('Fixture provider timed out.');
        }

        if ($spec['mode'] === 'invalid_json') {
            return new ProviderResponse(
                provider: $spec['provider'],
                model: 'fixture-model',
                providerStatus: 'success',
                extractionStatus: 'not_started',
                rawAnswer: 'not-json',
            );
        }

        return new ProviderResponse(
            provider: $spec['provider'],
            model: 'fixture-model',
            providerStatus: 'success',
            extractionStatus: 'not_started',
            rawAnswer: json_encode([
                'answer_shape' => 'discrete',
                'direct_answer' => $spec['direct_answer'],
                'summary' => $spec['summary'],
                'claims' => $spec['claims'],
                'citations' => [],
            ], JSON_THROW_ON_ERROR),
        );
    };
}
