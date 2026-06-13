<?php

namespace App\Consensus\DTO;

final readonly class TrustLevelResult
{
    /**
     * @param  'High'|'Medium'|'Low'|'Unknown'|''  $trustLevel
     * @param  array<array{condition: string, cap: string}>  $caps
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $trustLevel = '',
        public string $base = '',
        public int $analyzableProviderCount = 0,
        public int $effectiveDirectAnswerVoteCount = -1,
        public array $caps = [],
        public array $metadata = [],
    ) {}
}
