<?php

namespace App\Consensus\DTO;

final readonly class VerdictReport
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $verdict = '',
        public string $summary = '',
        public array $metadata = [],
    ) {}
}
