<?php

namespace App\Consensus\DTO;

final readonly class AlignmentResult
{
    /**
     * @param  array<string, mixed>  $aligned
     * @param  array<string, mixed>  $unmatched
     * @param  array<string, mixed>  $unalignable
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public array $aligned = [],
        public array $unmatched = [],
        public array $unalignable = [],
        public array $metadata = [],
    ) {}
}
