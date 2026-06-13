<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;

interface LlmProvider
{
    public function name(): string;

    /**
     * @throws ProviderException on provider_status != success paths
     */
    public function ask(Question $question, string $prompt): ProviderResponse;
}
