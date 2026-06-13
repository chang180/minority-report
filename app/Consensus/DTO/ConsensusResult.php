<?php

namespace App\Consensus\DTO;

final readonly class ConsensusResult
{
    /**
     * @param  array<string, mixed>  $conflicts
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $status = '',
        public ?string $majorityProvider = null,
        public ?string $minorityProvider = null,
        public array $conflicts = [],
        public array $metadata = [],
    ) {}
}
