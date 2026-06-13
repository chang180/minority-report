<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;

class NullLlmProvider implements LlmProvider
{
    public function name(): string
    {
        return 'null';
    }

    public function ask(Question $question, string $prompt): ProviderResponse
    {
        throw new \RuntimeException('Not implemented until M3');
    }
}
