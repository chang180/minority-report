<?php

use App\AI\Providers\AiTextProviderFactory;
use App\AI\Providers\AnthropicLlmProvider;
use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\AI\Providers\ConfiguredRawAnswerAgent;
use App\AI\Providers\GeminiLlmProvider;
use App\AI\Providers\LlmConnectionConfig;
use App\AI\Providers\OpenAiLlmProvider;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\Question;
use Laravel\Ai\Prompts\AgentPrompt;

test('sdk adapters prompt laravel ai with configured provider and model', function (
    string $providerClass,
    string $providerName,
    string $model,
) {
    ConfiguredRawAnswerAgent::fake(fn (): array => [
        'direct_answer' => 'yes',
        'summary' => "SDK response for {$providerName}:{$model}",
        'claims' => [],
        'citations' => [],
    ])->preventStrayPrompts();

    $connection = new LlmConnectionConfig(
        aiProviderKey: $providerName,
        enabled: true,
        model: $model,
        timeout: 17,
    );

    $provider = new $providerClass($connection, textProviderFactory: app(AiTextProviderFactory::class));

    $response = $provider->ask(
        new Question('What is Laravel?'),
        'Answer exactly once.',
    );

    expect($provider)->toBeInstanceOf(LlmProvider::class)
        ->and($provider->name())->toBe($providerName)
        ->and($response->provider)->toBe($providerName)
        ->and($response->model)->toBe($model)
        ->and($response->providerStatus)->toBe('success')
        ->and($response->extractionStatus)->toBe('not_started')
        ->and($response->rawAnswer)->toContain("SDK response for {$providerName}:{$model}")
        ->and($response->metadata['structured_output'])->toBeTrue()
        ->and($response->usage)->toHaveKeys([
            'provider_input_tokens',
            'provider_output_tokens',
            'estimated_cost',
        ])
        ->and($response->metadata)->toHaveKeys([
            'ai_provider',
            'invocation_id',
            'citations',
            'structured_output',
        ]);

    ConfiguredRawAnswerAgent::assertPrompted(function (AgentPrompt $prompt) use ($providerName, $model): bool {
        return $prompt->prompt === 'Answer exactly once.'
            && $prompt->provider()->name() === $providerName
            && $prompt->model === $model
            && $prompt->timeout === 17;
    });
})->with([
    'openai' => [OpenAiLlmProvider::class, 'openai', 'gpt-test'],
    'anthropic' => [AnthropicLlmProvider::class, 'anthropic', 'claude-test'],
    'gemini' => [GeminiLlmProvider::class, 'gemini', 'gemini-test'],
]);

test('disabled adapter returns provider unavailable without prompting sdk', function () {
    ConfiguredRawAnswerAgent::fake()->preventStrayPrompts();

    $connection = new LlmConnectionConfig(
        aiProviderKey: 'openai',
        enabled: false,
        model: 'gpt-test',
    );

    $response = (new OpenAiLlmProvider($connection, textProviderFactory: app(AiTextProviderFactory::class)))->ask(
        new Question('What is Laravel?'),
        'Prompt should not be sent.',
    );

    expect($response->provider)->toBe('openai')
        ->and($response->model)->toBe('gpt-test')
        ->and($response->providerStatus)->toBe('provider_unavailable')
        ->and($response->extractionStatus)->toBe('not_started')
        ->and($response->error['message'])->toBe('Provider is disabled or missing credentials.');

    ConfiguredRawAnswerAgent::assertNeverPrompted();
});

test('configured provider factory wires openai anthropic and gemini from config', function () {
    config()->set('consensus.providers.openai.enabled', true);
    config()->set('consensus.providers.openai.model', 'gpt-configured');
    config()->set('consensus.providers.anthropic.enabled', false);
    config()->set('consensus.providers.anthropic.model', 'claude-configured');
    config()->set('consensus.providers.gemini.enabled', true);
    config()->set('consensus.providers.gemini.model', 'gemini-configured');
    config()->set('consensus.timeouts.provider_seconds', 23);

    $factory = app(ConfiguredLlmProviderFactory::class);
    $providers = $factory->all();

    expect($providers)->toHaveCount(3)
        ->and($providers[0])->toBeInstanceOf(OpenAiLlmProvider::class)
        ->and($providers[1])->toBeInstanceOf(AnthropicLlmProvider::class)
        ->and($providers[2])->toBeInstanceOf(GeminiLlmProvider::class)
        ->and(app(LlmProvider::class))->toBeInstanceOf(OpenAiLlmProvider::class);

    ConfiguredRawAnswerAgent::fake(['configured response'])->preventStrayPrompts();

    $response = $providers[1]->ask(new Question('Question'), 'Prompt');

    expect($response->provider)->toBe('anthropic')
        ->and($response->providerStatus)->toBe('provider_unavailable');

    ConfiguredRawAnswerAgent::assertNeverPrompted();
});

test('live openai adapter can call laravel ai sdk when explicitly enabled', function () {
    if (! env('M3_B_LIVE_OPENAI')) {
        $this->markTestSkipped('Set M3_B_LIVE_OPENAI=1 with OPENAI_API_KEY to run the live adapter check.');
    }

    config()->set('consensus.providers.openai.enabled', true);

    $response = app(ConfiguredLlmProviderFactory::class)
        ->openai()
        ->ask(new Question('Say pong.'), 'Reply with exactly: pong');

    expect($response->provider)->toBe('openai')
        ->and($response->providerStatus)->toBe('success')
        ->and($response->rawAnswer)->not->toBeEmpty();
});
