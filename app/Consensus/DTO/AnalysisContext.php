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
        /** -1 = not applicable (open questions); ≥0 = count of non-unknown direct_answer votes */
        public int $effectiveVoteCount = -1,
        public array $metadata = [],
    ) {}
}
