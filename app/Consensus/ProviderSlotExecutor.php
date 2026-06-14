<?php

namespace App\Consensus;

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\AI\Providers\LlmConnectionConfig;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;

/**
 * Executes a single provider slot. Used by parallel dispatch with only scalar/array
 * arguments so Concurrency::run() does not serialize the full orchestrator graph.
 */
class ProviderSlotExecutor
{
    public function __construct(
        private readonly ProviderQueryService $queryService,
        private readonly ProviderResponseRepository $repository,
        private readonly ConfiguredLlmProviderFactory $providerFactory,
    ) {}

    /**
     * @param  array<string, mixed>  $connectionData
     * @param  array<string, mixed>  $questionMetadata
     */
    public function queryConfiguredSlotAndPersist(
        int $verificationRequestId,
        string $logicalName,
        array $connectionData,
        string $questionText,
        array $questionMetadata,
        string $prompt,
    ): ProviderResponse {
        $question = new Question(text: $questionText, metadata: $questionMetadata);
        $connection = LlmConnectionConfig::fromArray($connectionData);

        $response = $this->queryService->queryConfiguredSlotWithRetry(
            logicalName: $logicalName,
            connection: $connection,
            question: $question,
            prompt: $prompt,
            factory: $this->providerFactory,
        );

        $this->repository->save($verificationRequestId, $response, $prompt);

        return $response;
    }

    public function queryAndPersist(
        int $verificationRequestId,
        LlmProvider $provider,
        Question $question,
        string $prompt,
    ): ProviderResponse {
        $response = $this->queryService->queryWithRetry($provider, $question, $prompt);
        $this->repository->save($verificationRequestId, $response, $prompt);

        return $response;
    }
}
