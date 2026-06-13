<?php

namespace App\Consensus\DTO;

final readonly class TrustLevelResult
{
    /**
     * @param  'High'|'Medium'|'Low'|'Unknown'|''  $trustLevel
     * @param  string[]  $caps
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $trustLevel = '',
        public string $base = '',
        public array $caps = [],
        public array $metadata = [],
    ) {}
}
