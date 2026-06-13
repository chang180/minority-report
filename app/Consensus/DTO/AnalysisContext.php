<?php

namespace App\Consensus\DTO;

final readonly class AnalysisContext
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public bool $groundingAvailable = false,
        public int $providerCount = 0,
        public int $analyzableCount = 0,
        public array $metadata = [],
    ) {}
}
