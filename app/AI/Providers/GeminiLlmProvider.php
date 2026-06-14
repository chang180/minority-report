<?php

namespace App\AI\Providers;

use Laravel\Ai\Enums\Lab;

class GeminiLlmProvider extends LaravelAiLlmProvider
{
    public function __construct(
        LlmConnectionConfig $connection,
        array $providerOptions = [],
        ?AiTextProviderFactory $textProviderFactory = null,
    ) {
        parent::__construct('gemini', Lab::Gemini, $connection, $providerOptions, textProviderFactory: $textProviderFactory);
    }
}
