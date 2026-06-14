<?php

namespace App\AI\Providers;

use App\AI\Providers\Contracts\ConnectionConfiguredLlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderException;
use App\Consensus\Exceptions\ProviderTimeoutException;
use Illuminate\Http\Client\ConnectionException;
use Laravel\Ai\Ai;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Contracts\Providers\TextProvider;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Prompts\AgentPrompt;
use Laravel\Ai\Responses\AgentResponse;
use Laravel\Ai\Responses\StructuredAgentResponse;
use Throwable;

abstract class LaravelAiLlmProvider implements ConnectionConfiguredLlmProvider
{
    private readonly Agent $agent;

    public function __construct(
        private readonly string $providerName,
        private readonly Lab $lab,
        private readonly LlmConnectionConfig $connection,
        array $providerOptions = [],
        ?Agent $agent = null,
        private readonly ?AiTextProviderFactory $textProviderFactory = null,
    ) {
        $options = $providerOptions !== [] ? $providerOptions : $connection->providerOptions;
        $this->agent = $agent ?? new ConfiguredRawAnswerAgent($options);
    }

    public function name(): string
    {
        return $this->providerName;
    }

    public function connectionConfig(): LlmConnectionConfig
    {
        return $this->connection;
    }

    public function ask(Question $question, string $prompt): ProviderResponse
    {
        if (! $this->connection->enabled) {
            return new ProviderResponse(
                provider: $this->providerName,
                model: $this->connection->model ?: '',
                providerStatus: 'provider_unavailable',
                extractionStatus: 'not_started',
                error: ['message' => 'Provider is disabled or missing credentials.'],
            );
        }

        try {
            $response = $this->invokeAgent($question, $prompt);
        } catch (Throwable $exception) {
            if ($this->isTimeoutException($exception)) {
                throw new ProviderTimeoutException($exception->getMessage(), previous: $exception);
            }

            throw new ProviderException($exception->getMessage(), previous: $exception);
        }

        return new ProviderResponse(
            provider: $this->providerName,
            model: $response->meta->model ?: ($this->connection->model ?: ''),
            providerStatus: 'success',
            extractionStatus: 'not_started',
            rawAnswer: $this->resolveRawAnswer($response),
            usage: [
                'provider_input_tokens' => $response->usage->promptTokens,
                'provider_output_tokens' => $response->usage->completionTokens,
                'provider_cache_write_input_tokens' => $response->usage->cacheWriteInputTokens,
                'provider_cache_read_input_tokens' => $response->usage->cacheReadInputTokens,
                'provider_reasoning_tokens' => $response->usage->reasoningTokens,
                'estimated_cost' => null,
            ],
            metadata: [
                'ai_provider' => $response->meta->provider,
                'invocation_id' => $response->invocationId,
                'citations' => $response->meta->citations->all(),
                'structured_output' => $response instanceof StructuredAgentResponse,
            ],
        );
    }

    private function invokeAgent(Question $question, string $prompt): AgentResponse
    {
        $message = $prompt !== '' ? $prompt : $question->text;
        $timeout = $this->connection->timeout;
        $model = $this->connection->model;

        $textProvider = $this->resolveTextProvider();
        $resolvedModel = $model ?: $textProvider->defaultTextModel();

        return $textProvider->prompt(new AgentPrompt(
            agent: $this->agent,
            prompt: $message,
            attachments: [],
            provider: $textProvider,
            model: $resolvedModel,
            timeout: $timeout,
        ));
    }

    private function resolveTextProvider(): TextProvider
    {
        if ($this->textProviderFactory === null) {
            throw new ProviderException('AiTextProviderFactory is required for instance-scoped providers.');
        }

        $textProvider = $this->textProviderFactory->make($this->connection);

        if ($this->agent::isFaked()) {
            $textProvider = (clone $textProvider)->useTextGateway(Ai::fakeGatewayFor($this->agent));
        }

        return $textProvider;
    }

    private function resolveRawAnswer(AgentResponse $response): string
    {
        if ($response instanceof StructuredAgentResponse) {
            $structured = $response->structured ?? [];

            if ($structured !== [] && ! array_is_list($structured)) {
                return $response->toJson(JSON_UNESCAPED_UNICODE);
            }

            return $response->text !== '' ? $response->text : '[]';
        }

        return $response->text;
    }

    private function isTimeoutException(Throwable $exception): bool
    {
        return $exception instanceof ConnectionException
            || str_contains($exception::class, 'Timeout')
            || str_contains(strtolower($exception->getMessage()), 'timed out');
    }
}
