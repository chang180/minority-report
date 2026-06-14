<?php

namespace App\Consensus\Synthesis;

use App\Consensus\Contracts\LlmProvider;

final readonly class SynthesisRequest
{
    public function __construct(
        public bool $enabled,
        public string $synthesizerSlot,
        public LlmProvider $synthesizerProvider,
    ) {}
}
