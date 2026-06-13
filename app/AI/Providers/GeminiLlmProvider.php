<?php

namespace App\AI\Providers;

use Laravel\Ai\Enums\Lab;

class GeminiLlmProvider extends LaravelAiLlmProvider
{
    public function __construct(
        bool $enabled = true,
        ?string $model = null,
        int $timeout = 60,
        RawAnswerAgent $agent = new RawAnswerAgent,
    ) {
        parent::__construct('gemini', Lab::Gemini, $enabled, $model, $timeout, $agent);
    }
}
