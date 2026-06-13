<?php

namespace App\Consensus\Fake;

use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;

class FakeLlmProvider implements LlmProvider
{
    public function __construct(
        private readonly string $providerName,
        private readonly \Closure $behavior,
    ) {}

    public function name(): string
    {
        return $this->providerName;
    }

    public function ask(Question $question, string $prompt): ProviderResponse
    {
        return ($this->behavior)($question, $prompt);
    }
}
