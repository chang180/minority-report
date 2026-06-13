<?php

namespace App\AI\Providers;

use App\Consensus\Contracts\LlmProvider;
use Illuminate\Contracts\Config\Repository as Config;

class ConfiguredLlmProviderFactory
{
    public function __construct(
        private readonly Config $config,
    ) {}

    /**
     * @return LlmProvider[]
     */
    public function all(): array
    {
        return [
            $this->openai(),
            $this->anthropic(),
            $this->gemini(),
        ];
    }

    public function default(): LlmProvider
    {
        return $this->openai();
    }

    public function openai(): OpenAiLlmProvider
    {
        return new OpenAiLlmProvider(
            enabled: $this->providerEnabled('openai'),
            model: $this->providerModel('openai'),
            timeout: $this->providerTimeout(),
        );
    }

    public function anthropic(): AnthropicLlmProvider
    {
        return new AnthropicLlmProvider(
            enabled: $this->providerEnabled('anthropic'),
            model: $this->providerModel('anthropic'),
            timeout: $this->providerTimeout(),
        );
    }

    public function gemini(): GeminiLlmProvider
    {
        return new GeminiLlmProvider(
            enabled: $this->providerEnabled('gemini'),
            model: $this->providerModel('gemini'),
            timeout: $this->providerTimeout(),
        );
    }

    private function providerEnabled(string $provider): bool
    {
        return (bool) $this->config->get("consensus.providers.{$provider}.enabled", false);
    }

    private function providerModel(string $provider): ?string
    {
        $model = $this->config->get("consensus.providers.{$provider}.model");

        return is_string($model) && $model !== '' ? $model : null;
    }

    private function providerTimeout(): int
    {
        return (int) $this->config->get('consensus.timeouts.provider_seconds', 60);
    }
}
