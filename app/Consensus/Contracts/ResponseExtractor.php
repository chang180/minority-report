<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;

interface ResponseExtractor
{
    /**
     * Extract normalized DTO from a SINGLE provider's raw answer.
     * MUST NOT receive answers from multiple providers.
     */
    public function extract(
        ProviderResponse $providerResponse,
        ClassificationResult $classification,
    ): ProviderResponse;
}
