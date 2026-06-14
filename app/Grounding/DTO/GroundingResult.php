<?php

namespace App\Grounding\DTO;

readonly class GroundingResult
{
    /**
     * @param  GroundingSource[]  $sources
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $status,
        public bool $groundingAvailable,
        public string $query,
        public string $summary,
        public array $sources,
        public string $providerMode,
        public array $metadata = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toMetadataArray(): array
    {
        return [
            'status' => $this->status,
            'provider_mode' => $this->providerMode,
            'query' => $this->query,
            'summary' => $this->summary,
            'sources' => array_map(fn (GroundingSource $s) => $s->toArray(), $this->sources),
            'metadata' => $this->metadata,
        ];
    }

    public static function skipped(string $query = ''): self
    {
        return new self(
            status: 'skipped',
            groundingAvailable: false,
            query: $query,
            summary: '',
            sources: [],
            providerMode: 'disabled',
        );
    }

    public static function failed(string $query, string $providerMode, string $reason = ''): self
    {
        return new self(
            status: 'failed',
            groundingAvailable: false,
            query: $query,
            summary: '',
            sources: [],
            providerMode: $providerMode,
            metadata: ['reason' => $reason],
        );
    }
}
