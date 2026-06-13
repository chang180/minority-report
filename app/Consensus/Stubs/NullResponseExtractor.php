<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;

class NullResponseExtractor implements ResponseExtractor
{
    public function extract(
        ProviderResponse $providerResponse,
        ClassificationResult $classification,
    ): ProviderResponse {
        throw new \RuntimeException('Not implemented until M3');
    }
}
