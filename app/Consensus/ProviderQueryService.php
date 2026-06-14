<?php

namespace App\Consensus;

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\AI\Providers\Contracts\ConnectionConfiguredLlmProvider;
use App\AI\Providers\LlmConnectionConfig;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderException;
use App\Consensus\Exceptions\ProviderTimeoutException;

class ProviderQueryService
{
    public function __construct(
        private readonly int $maxRetries = 1,
    ) {}

    public function queryWithRetry(LlmProvider $provider, Question $question, string $prompt): ProviderResponse
    {
        $attempts = 0;

        while (true) {
            $attempts++;

            try {
                return $provider->ask($question, $prompt);
            } catch (ProviderTimeoutException $e) {
                if ($attempts > $this->maxRetries) {
                    return new ProviderResponse(
                        provider: $provider->name(),
                        providerStatus: 'failed_timeout',
                        extractionStatus: 'not_started',
                        error: ['message' => $e->getMessage()],
                    );
                }
            } catch (ProviderException $e) {
                return new ProviderResponse(
                    provider: $provider->name(),
                    providerStatus: 'provider_error',
                    extractionStatus: 'not_started',
                    error: ['message' => $e->getMessage()],
                );
            } catch (\Throwable $e) {
                return new ProviderResponse(
                    provider: $provider->name(),
                    providerStatus: 'provider_error',
                    extractionStatus: 'not_started',
                    error: ['message' => $e->getMessage(), 'class' => $e::class],
                );
            }
        }
    }

    public function queryConfiguredSlotWithRetry(
        string $logicalName,
        LlmConnectionConfig $connection,
        Question $question,
        string $prompt,
        ConfiguredLlmProviderFactory $factory,
    ): ProviderResponse {
        return $this->queryWithRetry(
            $factory->fromConnection($logicalName, $connection),
            $question,
            $prompt,
        );
    }

    /**
     * @param  LlmProvider[]  $providers
     * @return array<int, array{logical_name: string, connection: LlmConnectionConfig}|null>
     */
    public function serializableSlots(array $providers): array
    {
        $slots = [];

        foreach ($providers as $index => $provider) {
            if ($provider instanceof ConnectionConfiguredLlmProvider) {
                $slots[$index] = [
                    'logical_name' => $provider->name(),
                    'connection' => $provider->connectionConfig(),
                ];
            } else {
                $slots[$index] = null;
            }
        }

        return $slots;
    }
}
