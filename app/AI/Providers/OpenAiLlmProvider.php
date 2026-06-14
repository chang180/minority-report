<?php

namespace App\AI\Providers;

use Laravel\Ai\Enums\Lab;

class OpenAiLlmProvider extends LaravelAiLlmProvider
{
    public function __construct(
        LlmConnectionConfig $connection,
        array $providerOptions = [],
        ?AiTextProviderFactory $textProviderFactory = null,
    ) {
        parent::__construct('openai', Lab::OpenAI, $connection, $providerOptions, textProviderFactory: $textProviderFactory);
    }
}
