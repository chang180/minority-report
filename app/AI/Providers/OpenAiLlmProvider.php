<?php

namespace App\AI\Providers;

use Laravel\Ai\Enums\Lab;

class OpenAiLlmProvider extends LaravelAiLlmProvider
{
    public function __construct(
        bool $enabled = true,
        ?string $model = null,
        int $timeout = 60,
        array $providerOptions = [],
    ) {
        parent::__construct('openai', Lab::OpenAI, $enabled, $model, $timeout, $providerOptions);
    }
}
