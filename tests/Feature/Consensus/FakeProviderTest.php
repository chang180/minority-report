<?php

use App\Consensus\Contracts\FakeProviderRegistry;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Fake\FakeLlmProvider;
use App\Consensus\Fake\InMemoryFakeProviderRegistry;
use App\Consensus\ProviderOrchestrator;
use App\Models\VerificationRequest;
use App\Repositories\EloquentProviderResponseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('registry creates a fake provider that returns the registered behavior', function () {
    $registry = new InMemoryFakeProviderRegistry;

    $registry->register('F01_openai', function (Question $q, string $p): ProviderResponse {
        return new ProviderResponse(
            provider: 'openai',
            model: 'fake-gpt',
            providerStatus: 'success',
            extractionStatus: 'not_started',
            rawAnswer: 'Yes, Laravel migrations manage database schema changes.',
        );
    });

    $provider = $registry->create('F01_openai');

    expect($provider)->toBeInstanceOf(FakeLlmProvider::class)
        ->and($provider->name())->toBe('F01_openai');

    $response = $provider->ask(
        new Question('What are Laravel migrations?'),
        'Answer the question concisely.'
    );

    expect($response->provider)->toBe('openai')
        ->and($response->providerStatus)->toBe('success')
        ->and($response->extractionStatus)->toBe('not_started')
        ->and($response->rawAnswer)->toContain('Yes');
});

test('registry throws InvalidArgumentException for unregistered fixture id', function () {
    $registry = new InMemoryFakeProviderRegistry;

    expect(fn () => $registry->create('UNKNOWN'))
        ->toThrow(InvalidArgumentException::class, "No behavior registered for fixture 'UNKNOWN'");
});

test('registry can be resolved from DI container', function () {
    $registry = app(FakeProviderRegistry::class);

    expect($registry)->toBeInstanceOf(InMemoryFakeProviderRegistry::class);
});

test('registry is a singleton in the DI container', function () {
    $a = app(FakeProviderRegistry::class);
    $b = app(FakeProviderRegistry::class);

    expect($a)->toBe($b);
});

// F01 — Full Consensus (discrete): all three providers return success
test('F01 replay: three providers all succeed and responses are persisted', function () {
    $registry = new InMemoryFakeProviderRegistry;
    $question = new Question('Is PHP 8.4 the current stable version?');
    $prompt = 'Answer concisely: Is PHP 8.4 the current stable version?';

    $successBehavior = fn (string $providerName) => function (Question $q, string $p) use ($providerName): ProviderResponse {
        return new ProviderResponse(
            provider: $providerName,
            model: 'fake-model',
            providerStatus: 'success',
            extractionStatus: 'not_started',
            rawAnswer: "Yes. PHP 8.4 is the current stable version. [{$providerName}]",
        );
    };

    $registry->register('F01_openai', $successBehavior('openai'));
    $registry->register('F01_anthropic', $successBehavior('anthropic'));
    $registry->register('F01_gemini', $successBehavior('gemini'));

    $verificationRequest = VerificationRequest::create(['question' => $question->text]);
    $orchestrator = app(ProviderOrchestrator::class);

    $responses = $orchestrator->dispatch(
        $verificationRequest->id,
        $question,
        $prompt,
        [
            $registry->create('F01_openai'),
            $registry->create('F01_anthropic'),
            $registry->create('F01_gemini'),
        ],
    );

    // Three responses returned
    expect($responses)->toHaveCount(3);

    foreach ($responses as $response) {
        expect($response->providerStatus)->toBe('success')
            ->and($response->extractionStatus)->toBe('not_started')
            ->and($response->rawAnswer)->toContain('Yes');
    }

    // Providers are distinct
    $providerNames = array_column($responses, 'provider');
    expect($providerNames)->toContain('openai')
        ->toContain('anthropic')
        ->toContain('gemini');

    // All three persisted to DB
    expect(
        App\Models\ProviderResponse::where('verification_request_id', $verificationRequest->id)->count()
    )->toBe(3);

    // Each DB record has expected status
    App\Models\ProviderResponse::where('verification_request_id', $verificationRequest->id)
        ->each(function (App\Models\ProviderResponse $record) {
            expect($record->provider_status)->toBe('success')
                ->and($record->provider_prompt)->not->toBeEmpty()
                ->and($record->raw_answer)->toContain('Yes');
        });
});
