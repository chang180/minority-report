<?php

namespace App\Consensus\DTO;

final readonly class ProviderResponse
{
    /**
     * @param  'success'|'failed_timeout'|'provider_unavailable'|'provider_error'  $providerStatus
     * @param  'not_started'|'success'|'invalid_json'|'extraction_failed'  $extractionStatus
     * @param  array<string, mixed>|null  $normalized
     * @param  array<string, int|float|null>  $usage
     * @param  array<string, mixed>|null  $error
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $provider = '',
        public string $model = '',
        public string $providerStatus = 'provider_unavailable',
        public string $extractionStatus = 'not_started',
        public string $rawAnswer = '',
        public ?array $normalized = null,
        public array $usage = [],
        public ?array $error = null,
        public array $metadata = [],
    ) {}
}
