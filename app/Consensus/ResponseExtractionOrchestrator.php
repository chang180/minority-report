<?php

namespace App\Consensus;

use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;

class ResponseExtractionOrchestrator
{
    public function __construct(
        private readonly ResponseExtractor $extractor,
        private readonly ProviderResponseRepository $repository,
        private readonly VerificationWorkflowProgress $workflowProgress,
    ) {}

    /**
     * Extract each provider response independently and persist extraction fields.
     * Runs sequentially — extraction is local CPU work; process-based concurrency
     * would serialize heavy closures without latency benefit.
     *
     * @param  ProviderResponse[]  $providerResponses
     * @return ProviderResponse[]
     */
    public function extractAndPersist(
        int $verificationRequestId,
        array $providerResponses,
        ClassificationResult $classification,
    ): array {
        $this->workflowProgress->setPhase($verificationRequestId, VerificationWorkflowProgress::PHASE_EXTRACTING);

        $results = [];

        foreach ($providerResponses as $response) {
            $extracted = $this->extractor->extract($response, $classification);
            $this->repository->updateExtraction($verificationRequestId, $extracted);
            $results[] = $extracted;
        }

        return $results;
    }
}
