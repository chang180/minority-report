<?php

namespace App\Consensus;

use App\Consensus\Contracts\LlmProvider;
use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderException;
use App\Consensus\Exceptions\ProviderTimeoutException;

class ProviderOrchestrator
{
    public function __construct(
        private readonly ProviderResponseRepository $repository,
        private readonly int $maxRetries = 1,
    ) {}

    /**
     * Query all providers and persist each result. A single-provider failure does not interrupt others.
     *
     * Note: currently runs sequentially. M3-B will introduce true concurrency via Fibers or async adapters
     * when bridging to real LLM APIs.
     *
     * @param  LlmProvider[]  $providers
     * @return ProviderResponse[]
     */
    public function dispatch(
        int $verificationRequestId,
        Question $question,
        string $prompt,
        array $providers,
    ): array {
        return array_values(array_map(
            fn (LlmProvider $provider) => $this->queryAndPersist(
                $verificationRequestId, $provider, $question, $prompt
            ),
            $providers,
        ));
    }

    private function queryAndPersist(
        int $verificationRequestId,
        LlmProvider $provider,
        Question $question,
        string $prompt,
    ): ProviderResponse {
        $response = $this->queryWithRetry($provider, $question, $prompt);
        $this->repository->save($verificationRequestId, $response, $prompt);

        return $response;
    }

    private function queryWithRetry(
        LlmProvider $provider,
        Question $question,
        string $prompt,
    ): ProviderResponse {
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
                // Retry once on timeout
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
}
