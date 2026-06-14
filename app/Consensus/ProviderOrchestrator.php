<?php

namespace App\Consensus;

use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;

class ProviderOrchestrator
{
    public function __construct(
        private readonly ProviderSlotExecutor $slotExecutor,
        private readonly ProviderQueryService $queryService,
        private readonly ConsensusParallelRunner $parallelRunner,
        private readonly VerificationWorkflowProgress $workflowProgress,
    ) {}

    /**
     * Query all providers in parallel and persist each result as it completes.
     * A single-provider failure does not interrupt others.
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
        $this->workflowProgress->setPhase($verificationRequestId, VerificationWorkflowProgress::PHASE_DISPATCHING);

        $slots = $this->queryService->serializableSlots($providers);
        $useParallelSlots = $this->canRunParallel($providers, $slots);

        if ($useParallelSlots) {
            return $this->dispatchParallel(
                verificationRequestId: $verificationRequestId,
                question: $question,
                prompt: $prompt,
                slots: $slots,
            );
        }

        return $this->dispatchSequential(
            verificationRequestId: $verificationRequestId,
            question: $question,
            prompt: $prompt,
            providers: $providers,
        );
    }

    /**
     * @param  array<int, array{logical_name: string, connection: \App\AI\Providers\LlmConnectionConfig}>  $slots
     * @return ProviderResponse[]
     */
    private function dispatchParallel(
        int $verificationRequestId,
        Question $question,
        string $prompt,
        array $slots,
    ): array {
        $tasks = [];

        foreach ($slots as $index => $slot) {
            $connectionData = $slot['connection']->toArray();
            $questionText = $question->text;
            $questionMetadata = $question->metadata;
            $logicalName = $slot['logical_name'];

            $tasks[$index] = static fn () => app(ProviderSlotExecutor::class)->queryConfiguredSlotAndPersist(
                verificationRequestId: $verificationRequestId,
                logicalName: $logicalName,
                connectionData: $connectionData,
                questionText: $questionText,
                questionMetadata: $questionMetadata,
                prompt: $prompt,
            );
        }

        $results = $this->parallelRunner->run($tasks);
        ksort($results);

        return array_values($results);
    }

    /**
     * @param  LlmProvider[]  $providers
     * @return ProviderResponse[]
     */
    private function dispatchSequential(
        int $verificationRequestId,
        Question $question,
        string $prompt,
        array $providers,
    ): array {
        $results = [];

        foreach ($providers as $provider) {
            $results[] = $this->slotExecutor->queryAndPersist(
                verificationRequestId: $verificationRequestId,
                provider: $provider,
                question: $question,
                prompt: $prompt,
            );
        }

        return $results;
    }

    /**
     * @param  LlmProvider[]  $providers
     * @param  array<int, array{logical_name: string, connection: \App\AI\Providers\LlmConnectionConfig}|null>  $slots
     */
    private function canRunParallel(array $providers, array $slots): bool
    {
        if (count($providers) <= 1) {
            return false;
        }

        foreach ($slots as $slot) {
            if ($slot === null) {
                return false;
            }
        }

        return true;
    }
}
