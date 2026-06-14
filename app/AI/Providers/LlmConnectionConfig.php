<?php

namespace App\AI\Providers;

/**
 * Per-slot AI connection settings. Immutable so provider instances can run in parallel
 * without mutating global config.
 */
final readonly class LlmConnectionConfig
{
    /**
     * @param  array<string, mixed>  $providerOptions
     */
    public function __construct(
        public string $aiProviderKey,
        public bool $enabled,
        public ?string $model = null,
        public ?string $apiKey = null,
        public ?string $apiUrl = null,
        public int $timeout = 60,
        public array $providerOptions = [],
    ) {}

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return [
            'ai_provider_key' => $this->aiProviderKey,
            'enabled' => $this->enabled,
            'model' => $this->model,
            'api_key' => $this->apiKey,
            'api_url' => $this->apiUrl,
            'timeout' => $this->timeout,
            'provider_options' => $this->providerOptions,
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            aiProviderKey: (string) ($data['ai_provider_key'] ?? 'openai'),
            enabled: (bool) ($data['enabled'] ?? false),
            model: isset($data['model']) ? (string) $data['model'] : null,
            apiKey: isset($data['api_key']) ? (string) $data['api_key'] : null,
            apiUrl: isset($data['api_url']) ? (string) $data['api_url'] : null,
            timeout: (int) ($data['timeout'] ?? 60),
            providerOptions: is_array($data['provider_options'] ?? null) ? $data['provider_options'] : [],
        );
    }
}
