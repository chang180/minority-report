<?php

namespace App\AI\Providers;

use Illuminate\Contracts\Config\Repository as Config;
use Laravel\Ai\AiManager;
use Laravel\Ai\Contracts\Providers\TextProvider;

class AiTextProviderFactory
{
    public function __construct(
        private readonly AiManager $aiManager,
        private readonly Config $config,
    ) {}

    public function make(LlmConnectionConfig $connection): TextProvider
    {
        $base = $this->config->get('ai.providers.'.$connection->aiProviderKey, []);
        $driver = (string) ($base['driver'] ?? $connection->aiProviderKey);

        $providerConfig = array_merge($base, [
            'name' => $connection->aiProviderKey,
            'key' => $connection->apiKey ?? ($base['key'] ?? null),
        ]);

        // Strip empty string URL from base config to prevent invalid-scheme errors
        if (isset($providerConfig['url']) && $providerConfig['url'] === '') {
            unset($providerConfig['url']);
        }

        if ($connection->apiUrl !== null && $connection->apiUrl !== '') {
            $providerConfig['url'] = $connection->apiUrl;
        }

        return match ($driver) {
            'anthropic' => $this->aiManager->createAnthropicDriver($providerConfig),
            'gemini' => $this->aiManager->createGeminiDriver($providerConfig),
            'deepseek' => $this->aiManager->createDeepseekDriver($providerConfig),
            default => $this->aiManager->createOpenaiDriver($providerConfig),
        };
    }
}
