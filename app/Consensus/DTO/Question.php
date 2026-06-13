<?php

namespace App\Consensus\DTO;

final readonly class Question
{
    /**
     * @param  array<string, mixed>  $metadata
     */
    public function __construct(
        public string $text = '',
        public array $metadata = [],
    ) {}
}
