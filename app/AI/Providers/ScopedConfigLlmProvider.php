<?php

namespace App\AI\Providers;

use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use Illuminate\Contracts\Config\Repository as Config;

/**
 * Wraps a LaravelAiLlmProvider with temporary config overrides (e.g. per-user key/url).
 * Restores original config values after each call so sequential providers are isolated.
 */
class ScopedConfigLlmProvider implements LlmProvider
{
    /**
     * @param  array<string, mixed>  $configOverrides  config key → override value
     */
    public function __construct(
        private readonly string $logicalName,
        private readonly LaravelAiLlmProvider $inner,
        private readonly array $configOverrides,
        private readonly Config $config,
    ) {}

    public function name(): string
    {
        return $this->logicalName;
    }

    public function ask(Question $question, string $prompt): ProviderResponse
    {
        $originals = [];
        foreach ($this->configOverrides as $key => $value) {
            $originals[$key] = $this->config->get($key);
            $this->config->set($key, $value);
        }

        try {
            $response = $this->inner->ask($question, $prompt);

            return new ProviderResponse(
                provider: $this->logicalName,
                model: $response->model,
                providerStatus: $response->providerStatus,
                extractionStatus: $response->extractionStatus,
                rawAnswer: $response->rawAnswer,
                normalized: $response->normalized,
                usage: $response->usage,
                error: $response->error,
                metadata: $response->metadata,
                extractionPrompt: $response->extractionPrompt,
                extractorModel: $response->extractorModel,
            );
        } finally {
            foreach ($originals as $key => $value) {
                $this->config->set($key, $value);
            }
        }
    }
}
