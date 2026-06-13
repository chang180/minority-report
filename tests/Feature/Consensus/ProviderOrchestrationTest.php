<?php

use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderException;
use App\Consensus\Exceptions\ProviderTimeoutException;
use App\Consensus\Fake\InMemoryFakeProviderRegistry;
use App\Consensus\ProviderOrchestrator;
use App\Models\VerificationRequest;
use App\Repositories\EloquentProviderResponseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeOrchestrator(): ProviderOrchestrator
{
    return new ProviderOrchestrator(new EloquentProviderResponseRepository);
}

function makeRequest(): VerificationRequest
{
    return VerificationRequest::create(['question' => 'Test question']);
}

function makeQuestion(): Question
{
    return new Question('Test question');
}

// ─────────────────────────────────────────────
// Single-provider failure isolation
// ─────────────────────────────────────────────

test('single provider failure does not interrupt other providers', function () {
    $registry = new InMemoryFakeProviderRegistry;

    $registry->register('ok_P1', fn (Question $q, string $p): ProviderResponse => new ProviderResponse(
        provider: 'openai', providerStatus: 'success', extractionStatus: 'not_started', rawAnswer: 'P1 answer',
    ));

    $registry->register('fail_P2', function (Question $q, string $p): never {
        throw new ProviderException('Provider P2 unavailable');
    });

    $registry->register('ok_P3', fn (Question $q, string $p): ProviderResponse => new ProviderResponse(
        provider: 'gemini', providerStatus: 'success', extractionStatus: 'not_started', rawAnswer: 'P3 answer',
    ));

    $request = makeRequest();
    $responses = makeOrchestrator()->dispatch(
        $request->id,
        makeQuestion(),
        'prompt',
        [
            $registry->create('ok_P1'),
            $registry->create('fail_P2'),
            $registry->create('ok_P3'),
        ],
    );

    expect($responses)->toHaveCount(3)
        ->and($responses[0]->providerStatus)->toBe('success')
        ->and($responses[1]->providerStatus)->toBe('provider_error')
        ->and($responses[2]->providerStatus)->toBe('success');

    // All three outcomes persisted
    expect(App\Models\ProviderResponse::where('verification_request_id', $request->id)->count())->toBe(3);
});

test('provider_unavailable is returned when provider throws generic exception', function () {
    $registry = new InMemoryFakeProviderRegistry;

    $registry->register('bad_provider', function (Question $q, string $p): never {
        throw new ProviderException('API key missing');
    });

    $request = makeRequest();
    $responses = makeOrchestrator()->dispatch(
        $request->id,
        makeQuestion(),
        'prompt',
        [$registry->create('bad_provider')],
    );

    expect($responses[0]->providerStatus)->toBe('provider_error')
        ->and($responses[0]->extractionStatus)->toBe('not_started')
        ->and($responses[0]->error['message'])->toBe('API key missing');
});

// ─────────────────────────────────────────────
// Timeout retry logic
// ─────────────────────────────────────────────

test('provider is retried once on timeout and succeeds on second attempt', function () {
    $attempts = 0;
    $registry = new InMemoryFakeProviderRegistry;

    $registry->register('flaky_provider', function (Question $q, string $p) use (&$attempts): ProviderResponse {
        $attempts++;
        if ($attempts === 1) {
            throw new ProviderTimeoutException('Request timed out');
        }

        return new ProviderResponse(
            provider: 'flaky_provider',
            providerStatus: 'success',
            extractionStatus: 'not_started',
            rawAnswer: 'Recovered answer on retry',
        );
    });

    $request = makeRequest();
    $responses = makeOrchestrator()->dispatch(
        $request->id,
        makeQuestion(),
        'prompt',
        [$registry->create('flaky_provider')],
    );

    expect($responses[0]->providerStatus)->toBe('success')
        ->and($responses[0]->rawAnswer)->toBe('Recovered answer on retry')
        ->and($attempts)->toBe(2);
});

test('provider is marked failed_timeout after exhausting retries', function () {
    $attempts = 0;
    $registry = new InMemoryFakeProviderRegistry;

    $registry->register('always_timeout', function (Question $q, string $p) use (&$attempts): never {
        $attempts++;
        throw new ProviderTimeoutException('Always times out');
    });

    $request = makeRequest();
    $responses = makeOrchestrator()->dispatch(
        $request->id,
        makeQuestion(),
        'prompt',
        [$registry->create('always_timeout')],
    );

    expect($responses[0]->providerStatus)->toBe('failed_timeout')
        ->and($responses[0]->extractionStatus)->toBe('not_started')
        ->and($attempts)->toBe(2); // 1 initial + 1 retry

    // Failure is persisted
    $record = App\Models\ProviderResponse::where('verification_request_id', $request->id)->first();
    expect($record->provider_status)->toBe('failed_timeout');
});

// ─────────────────────────────────────────────
// Persistence completeness
// ─────────────────────────────────────────────

test('raw answer and prompt are persisted for each provider', function () {
    $registry = new InMemoryFakeProviderRegistry;
    $registry->register('persist_check', fn (Question $q, string $p): ProviderResponse => new ProviderResponse(
        provider: 'openai',
        model: 'gpt-fake',
        providerStatus: 'success',
        extractionStatus: 'not_started',
        rawAnswer: 'Persisted raw answer',
    ));

    $request = makeRequest();
    makeOrchestrator()->dispatch(
        $request->id,
        makeQuestion(),
        'The exact prompt text',
        [$registry->create('persist_check')],
    );

    $record = App\Models\ProviderResponse::where('verification_request_id', $request->id)->first();

    expect($record->provider)->toBe('openai')
        ->and($record->model)->toBe('gpt-fake')
        ->and($record->provider_status)->toBe('success')
        ->and($record->extraction_status)->toBe('not_started')
        ->and($record->raw_answer)->toBe('Persisted raw answer')
        ->and($record->provider_prompt)->toBe('The exact prompt text');
});
