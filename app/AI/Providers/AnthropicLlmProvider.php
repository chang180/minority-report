<?php

namespace App\AI\Providers;

use Laravel\Ai\Enums\Lab;

class AnthropicLlmProvider extends LaravelAiLlmProvider
{
    public function __construct(
        bool $enabled = true,
        ?string $model = null,
        int $timeout = 60,
        RawAnswerAgent $agent = new RawAnswerAgent,
    ) {
        parent::__construct('anthropic', Lab::Anthropic, $enabled, $model, $timeout, $agent);
    }
}
