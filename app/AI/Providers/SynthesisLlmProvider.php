<?php

namespace App\AI\Providers;

use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderException;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Prompts\AgentPrompt;
use Throwable;

/**
 * Plain-text LLM call for the synthesizer seat (non-structured output).
 */
class SynthesisLlmProvider implements LlmProvider
{
    private readonly ConfiguredSynthesisAgent $agent;

    public function __construct(
        private readonly string $logicalName,
        private readonly LlmConnectionConfig $connection,
        private readonly AiTextProviderFactory $textProviderFactory,
    ) {
        $this->agent = new ConfiguredSynthesisAgent;
    }

    public function name(): string
    {
        return $this->logicalName;
    }

    public function connectionConfig(): LlmConnectionConfig
    {
        return $this->connection;
    }

    public function ask(Question $question, string $prompt): ProviderResponse
    {
        try {
            $text = $this->synthesize($prompt);
        } catch (Throwable $exception) {
            return new ProviderResponse(
                provider: $this->logicalName,
                model: $this->connection->model ?: '',
                providerStatus: 'provider_error',
                extractionStatus: 'not_started',
                error: ['message' => $exception->getMessage(), 'class' => $exception::class],
            );
        }

        if ($text === null || trim($text) === '') {
            return new ProviderResponse(
                provider: $this->logicalName,
                model: $this->connection->model ?: '',
                providerStatus: 'provider_error',
                extractionStatus: 'not_started',
                error: ['message' => 'Synthesizer returned an empty response.'],
            );
        }

        return new ProviderResponse(
            provider: $this->logicalName,
            model: $this->connection->model ?: '',
            providerStatus: 'success',
            extractionStatus: 'not_started',
            rawAnswer: $text,
        );
    }

    public function synthesize(string $prompt): ?string
    {
        if (! $this->connection->enabled) {
            throw new ProviderException('Synthesizer provider is disabled or missing credentials.');
        }

        $textProvider = $this->resolveTextProvider();
        $model = $this->connection->model ?: $textProvider->defaultTextModel();

        $response = $textProvider->prompt(new AgentPrompt(
            agent: $this->agent,
            prompt: $prompt,
            attachments: [],
            provider: $textProvider,
            model: $model,
            timeout: $this->connection->timeout,
        ));

        $text = trim($response->text);

        return $text !== '' ? $text : null;
    }

    private function resolveTextProvider(): TextProvider
    {
        $textProvider = $this->textProviderFactory->make($this->connection);

        if ($this->agent::isFaked()) {
            $textProvider = (clone $textProvider)->useTextGateway(Ai::fakeGatewayFor($this->agent));
        }

        return $textProvider;
    }
}
