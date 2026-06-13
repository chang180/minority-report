<?php

use App\Consensus\ConsensusWorkflow;
use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderTimeoutException;
use App\Consensus\Fake\InMemoryFakeProviderRegistry;
use App\Consensus\Verdict\StructuredVerdictReporter;
use App\Models\ConsensusResult;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('M4-C fixture :dataset passes through classifier providers extractor aligner analyzer trust verdict and persistence', function (array $fixture) {
    $registry = new InMemoryFakeProviderRegistry;
    $providers = [];

    foreach ($fixture['providers'] as $spec) {
        $fixtureProviderId = $fixture['id'].'_'.$spec['provider'];
        $registry->register($fixtureProviderId, m4cProviderBehavior($spec, $fixture['shape']));
        $providers[] = $registry->create($fixtureProviderId);
    }

    $question = new Question(
        text: $fixture['question'],
        metadata: [
            'classification' => [
                'type' => $fixture['type'],
                'answer_shape' => $fixture['shape'],
                'requires_grounding' => $fixture['type'] === 'C',
                'classifier_confidence' => 'high',
            ],
            'fixture_id' => $fixture['id'],
            'grounding_available' => false,
        ],
    );

    $verificationRequest = app(ConsensusWorkflow::class)->run($question, $providers);

    expect($verificationRequest->classified_type)->toBe($fixture['type'])
        ->and($verificationRequest->answer_shape)->toBe($fixture['shape'])
        ->and($verificationRequest->providerResponses)->toHaveCount(3)
        ->and($verificationRequest->consensusResult)->not->toBeNull()
        ->and($verificationRequest->consensusResult->consensus['status'])->toBe($fixture['consensus'])
        ->and($verificationRequest->consensusResult->trust_level)->toBe($fixture['trust'])
        ->and($verificationRequest->final_trust)->toBe($fixture['trust'])
        ->and($verificationRequest->consensusResult->verdict_report['metadata']['has_minority_report'])
        ->toBe($fixture['minority_report']);

    if (array_key_exists('minority_provider', $fixture)) {
        expect($verificationRequest->consensusResult->consensus['minority_provider'])
            ->toBe($fixture['minority_provider']);
    }

    if (isset($fixture['expected_provider_statuses'])) {
        foreach ($fixture['expected_provider_statuses'] as $provider => $statuses) {
            $record = $verificationRequest->providerResponses->firstWhere('provider', $provider);

            expect($record)->not->toBeNull()
                ->and($record->provider_status)->toBe($statuses['provider_status'])
                ->and($record->extraction_status)->toBe($statuses['extraction_status']);
        }
    }

    if (($fixture['id'] ?? '') === 'F09') {
        expect($verificationRequest->final_verdict)->toContain('mechanically comparable');
    }

    if (($fixture['id'] ?? '') === 'F11') {
        expect($verificationRequest->final_verdict)->toBeNull()
            ->and($verificationRequest->consensusResult->verdict_report['summary'])
            ->toContain('No final answer');
    }

    if (($fixture['id'] ?? '') === 'F14') {
        expect($verificationRequest->final_verdict)->toContain('direct_answer split')
            ->and($verificationRequest->final_verdict)->toContain('launch date');
    }

    expect(ConsensusResult::whereBelongsTo($verificationRequest)->count())->toBe(1);
})->with(fn (): array => m4cFixtures());

test('consensus service provider wires the M4-C verdict reporter implementation', function () {
    expect(app(VerdictReporter::class))->toBeInstanceOf(StructuredVerdictReporter::class);
});

/**
 * @return array<string, array{0: array<string, mixed>}>
 */
function m4cFixtures(): array
{
    return collect([
        m4cFixture('F01', 'B', 'discrete', [
            m4cSuccess('openai', 'yes'),
            m4cSuccess('anthropic', 'yes'),
            m4cSuccess('gemini', 'yes'),
        ], 'Full', 'High', false),

        m4cFixture('F02', 'B', 'discrete', [
            m4cSuccess('openai', 'yes'),
            m4cSuccess('anthropic', 'yes'),
            m4cSuccess('gemini', 'no'),
        ], 'Majority', 'Medium', true, ['minority_provider' => 'gemini']),

        m4cFixture('F03', 'B', 'discrete', [
            m4cSuccess('openai', 'yes'),
            m4cSuccess('anthropic', 'no'),
            m4cSuccess('gemini', 'unknown'),
        ], 'None', 'Low', false),

        m4cFixture('F04', 'C', 'discrete', [
            m4cSuccess('openai', 'yes'),
            m4cSuccess('anthropic', 'yes'),
            m4cSuccess('gemini', 'yes'),
        ], 'Full', 'Low', false),

        m4cFixture('F05', 'B', 'discrete', [
            m4cTimeout('openai'),
            m4cSuccess('anthropic', 'yes'),
            m4cSuccess('gemini', 'yes'),
        ], 'Full (2-only)', 'Medium', false),

        m4cFixture('F06', 'B', 'discrete', [
            m4cInvalidJson('openai'),
            m4cSuccess('anthropic', 'yes'),
            m4cSuccess('gemini', 'yes'),
        ], 'Full (2-only)', 'Medium', false, [
            'expected_provider_statuses' => [
                'openai' => ['provider_status' => 'success', 'extraction_status' => 'invalid_json'],
            ],
        ]),

        m4cFixture('F07', 'B', 'discrete', [
            m4cSuccess('openai', 'yes', [m4cClaim('date', 'release date', '2024-03-15')]),
            m4cSuccess('anthropic', 'yes', [m4cClaim('date', 'release date', '2024-03-15')]),
            m4cSuccess('gemini', 'yes', [m4cClaim('date', 'release date', '2023-06-01')]),
        ], 'Majority', 'Low', true, ['minority_provider' => 'gemini']),

        m4cFixture('F08', 'B', 'open', [
            m4cSuccess('openai', 'not_applicable', [m4cClaim('boolean', 'laravel migration purpose', 'true')], 'Laravel migrations manage database schema changes.'),
            m4cSuccess('anthropic', 'not_applicable', [m4cClaim('boolean', 'laravel migration purpose', 'true')], 'Laravel migrations manage database schema changes.'),
            m4cSuccess('gemini', 'not_applicable', [m4cClaim('boolean', 'laravel migration purpose', 'true')], 'Laravel migrations manage database schema changes.'),
        ], 'Full', 'High', false),

        m4cFixture('F09', 'B', 'open', [
            m4cSuccess('openai', 'not_applicable', [m4cClaim('statement', 'laravel migration explanation', 'schema versioning')]),
            m4cSuccess('anthropic', 'not_applicable', [m4cClaim('statement', 'laravel migration explanation', 'schema versioning')]),
            m4cSuccess('gemini', 'not_applicable', [m4cClaim('statement', 'laravel migration explanation', 'schema versioning')]),
        ], 'Full (low-discriminability)', 'Medium', false),

        m4cFixture('F10', 'B', 'discrete', [
            m4cSuccess('openai', 'yes'),
            m4cInvalidJson('anthropic'),
            m4cTimeout('gemini'),
        ], 'Insufficient', 'Unknown', false),

        m4cFixture('F11', 'B', 'discrete', [
            m4cInvalidJson('openai'),
            m4cInvalidJson('anthropic'),
            m4cInvalidJson('gemini'),
        ], 'Failure', 'Unknown', false),

        m4cFixture('F12', 'B', 'discrete', [
            m4cSuccess('openai', 'yes', [m4cClaim('boolean', 'package maintained', 'true')]),
            m4cSuccess('anthropic', 'yes', [m4cClaim('boolean', 'package maintained', 'false')]),
            m4cTimeout('gemini'),
        ], 'None', 'Low', false),

        m4cFixture('F13', 'B', 'discrete', [
            m4cSuccess('openai', 'yes'),
            m4cSuccess('anthropic', 'yes'),
            m4cSuccess('gemini', 'unknown'),
        ], 'Full', 'Medium', false, ['minority_provider' => null]),

        m4cFixture('F14', 'B', 'discrete', [
            m4cSuccess('openai', 'yes', [m4cClaim('date', 'launch date', '2024-03')]),
            m4cSuccess('anthropic', 'yes', [m4cClaim('date', 'launch date', '2023-01')]),
            m4cSuccess('gemini', 'no', [m4cClaim('date', 'launch date', '2024-03')]),
        ], 'None', 'Low', false),
    ])->mapWithKeys(fn (array $fixture): array => [$fixture['id'] => [$fixture]])->all();
}

/**
 * @param  array<int, array<string, mixed>>  $providers
 * @param  array<string, mixed>  $overrides
 * @return array<string, mixed>
 */
function m4cFixture(
    string $id,
    string $type,
    string $shape,
    array $providers,
    string $consensus,
    string $trust,
    bool $minorityReport,
    array $overrides = [],
): array {
    return array_merge([
        'id' => $id,
        'question' => "Fixture {$id} question",
        'type' => $type,
        'shape' => $shape,
        'providers' => $providers,
        'consensus' => $consensus,
        'trust' => $trust,
        'minority_report' => $minorityReport,
    ], $overrides);
}

/**
 * @param  array<int, array<string, mixed>>  $claims
 * @return array<string, mixed>
 */
function m4cSuccess(string $provider, string $directAnswer, array $claims = [], ?string $summary = null): array
{
    return [
        'provider' => $provider,
        'mode' => 'success',
        'direct_answer' => $directAnswer,
        'claims' => $claims,
        'summary' => $summary ?? "{$provider} fixture answer",
    ];
}

/**
 * @return array<string, mixed>
 */
function m4cInvalidJson(string $provider): array
{
    return ['provider' => $provider, 'mode' => 'invalid_json'];
}

/**
 * @return array<string, mixed>
 */
function m4cTimeout(string $provider): array
{
    return ['provider' => $provider, 'mode' => 'timeout'];
}

/**
 * @return array<string, mixed>
 */
function m4cClaim(string $type, string $key, string $value, ?string $unit = null): array
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
function m4cProviderBehavior(array $spec, string $shape): Closure
{
    return function (Question $question, string $prompt) use ($spec, $shape): ProviderResponse {
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
                'answer_shape' => $shape,
                'direct_answer' => $spec['direct_answer'],
                'summary' => $spec['summary'],
                'claims' => $spec['claims'],
                'citations' => [],
            ], JSON_THROW_ON_ERROR),
        );
    };
}
