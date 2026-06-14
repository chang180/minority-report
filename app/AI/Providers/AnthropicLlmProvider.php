<?php

namespace App\AI\Providers;

use Laravel\Ai\Enums\Lab;

class AnthropicLlmProvider extends LaravelAiLlmProvider
{
    public function __construct(
        LlmConnectionConfig $connection,
        array $providerOptions = [],
        ?AiTextProviderFactory $textProviderFactory = null,
    ) {
        parent::__construct('anthropic', Lab::Anthropic, $connection, $providerOptions, textProviderFactory: $textProviderFactory);
    }
}
