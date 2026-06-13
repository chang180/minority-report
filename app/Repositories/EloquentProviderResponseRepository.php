<?php

namespace App\Repositories;

use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\DTO\ProviderResponse;
use App\Models\ProviderResponse as ProviderResponseModel;

class EloquentProviderResponseRepository implements ProviderResponseRepository
{
    public function save(int $verificationRequestId, ProviderResponse $response, ?string $providerPrompt = null): void
    {
        ProviderResponseModel::create([
            'verification_request_id' => $verificationRequestId,
            'provider' => $response->provider,
            'model' => $response->model ?: null,
            'provider_prompt' => $providerPrompt,
            'provider_status' => $response->providerStatus,
            'extraction_status' => $response->extractionStatus,
            'raw_answer' => $response->rawAnswer ?: null,
            'normalized' => $response->normalized,
            'usage' => $response->usage ?: null,
            'error' => $response->error,
            'metadata' => $response->metadata ?: null,
        ]);
    }
}
