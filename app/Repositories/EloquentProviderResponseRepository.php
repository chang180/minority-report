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

    public function updateExtraction(int $verificationRequestId, ProviderResponse $response): void
    {
        ProviderResponseModel::query()
            ->where('verification_request_id', $verificationRequestId)
            ->where('provider', $response->provider)
            ->latest('id')
            ->firstOrFail()
            ->update([
                'extraction_prompt' => $response->extractionPrompt ?: null,
                'extractor_model' => $response->extractorModel ?: null,
                'extraction_status' => $response->extractionStatus,
                'normalized' => $response->normalized,
                'usage' => $response->usage ?: null,
                'error' => $response->error,
                'metadata' => $response->metadata ?: null,
            ]);
    }
}
