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
    ) {}

    /**
     * Extract each provider response independently and persist extraction fields.
     *
     * @param  ProviderResponse[]  $providerResponses
     * @return ProviderResponse[]
     */
    public function extractAndPersist(
        int $verificationRequestId,
        array $providerResponses,
        ClassificationResult $classification,
    ): array {
        return array_values(array_map(
            fn (ProviderResponse $response): ProviderResponse => $this->extractOne(
                $verificationRequestId,
                $response,
                $classification,
            ),
            $providerResponses,
        ));
    }

    private function extractOne(
        int $verificationRequestId,
        ProviderResponse $response,
        ClassificationResult $classification,
    ): ProviderResponse {
        $extracted = $this->extractor->extract($response, $classification);
        $this->repository->updateExtraction($verificationRequestId, $extracted);

        return $extracted;
    }
}
