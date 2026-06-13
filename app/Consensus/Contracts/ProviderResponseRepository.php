<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\ProviderResponse;

interface ProviderResponseRepository
{
    public function save(int $verificationRequestId, ProviderResponse $response, ?string $providerPrompt = null): void;

    public function updateExtraction(int $verificationRequestId, ProviderResponse $response): void;
}
