<?php

namespace App\AI\Providers;

use App\Consensus\Contracts\LlmProvider;
use App\Models\SystemDemoSettings;
use App\Models\User;
use App\Models\UserCustomProvider;
use App\Models\UserProviderSettings;
use Illuminate\Contracts\Config\Repository as Config;

class ConfiguredLlmProviderFactory
{
    /** Logical slot names required by the consensus domain. */
    private const SLOTS = ['openai', 'anthropic', 'gemini'];

    /** Maps preset provider_key → AI config provider name. */
    private const PRESET_AI_KEY = [
        'openai' => 'openai',
        'anthropic' => 'anthropic',
        'gemini' => 'gemini',
        'ollama' => 'ollama',
        'groq' => 'groq',
    ];

    public function __construct(
        private readonly Config $config,
        private readonly AiTextProviderFactory $textProviderFactory,
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

    /**
     * Return three LlmProvider instances for the authenticated user's consensus slots.
     * Logical names are always openai / anthropic / gemini.
     *
     * @return LlmProvider[]
     */
    public function forUser(User $user): array
    {
        $slots = $user->consensus_slots ?? [];

        return array_map(
            fn (string $logicalName) => $this->resolveUserSlot($user, $logicalName, $slots[$logicalName] ?? null),
            self::SLOTS,
        );
    }

    /**
     * Return three LlmProvider instances for the demo, based on SystemDemoSettings.
     *
     * @return LlmProvider[]
     */
    public function forDemo(SystemDemoSettings $settings): array
    {
        if ($settings->mode === 'shared_local_api') {
            return $this->buildSharedLocalProviders($settings);
        }

        return [
            $this->disabledProvider('openai'),
            $this->disabledProvider('anthropic'),
            $this->disabledProvider('gemini'),
        ];
    }

    public function fromConnection(string $logicalName, LlmConnectionConfig $connection): LlmProvider
    {
        return $this->buildProvider($logicalName, $connection);
    }

    public function disabledSlot(string $logicalName): LlmProvider
    {
        return $this->disabledProvider($logicalName);
    }

    public function openai(): OpenAiLlmProvider
    {
        return new OpenAiLlmProvider(
            $this->envConnection('openai'),
            textProviderFactory: $this->textProviderFactory,
        );
    }

    public function anthropic(): AnthropicLlmProvider
    {
        return new AnthropicLlmProvider(
            $this->envConnection('anthropic'),
            textProviderFactory: $this->textProviderFactory,
        );
    }

    public function gemini(): GeminiLlmProvider
    {
        return new GeminiLlmProvider(
            $this->envConnection('gemini'),
            textProviderFactory: $this->textProviderFactory,
        );
    }

    /**
     * @param  array<string, mixed>|null  $slot
     */
    private function resolveUserSlot(User $user, string $logicalName, ?array $slot): LlmProvider
    {
        if ($slot === null) {
            return $this->disabledProvider($logicalName);
        }

        if ($slot['type'] === 'preset') {
            return $this->resolvePresetSlot($logicalName, $slot['provider_key'], $user);
        }

        if ($slot['type'] === 'custom') {
            return $this->resolveCustomSlot($logicalName, (int) $slot['custom_provider_id'], $user);
        }

        return $this->disabledProvider($logicalName);
    }

    private function resolvePresetSlot(string $logicalName, string $providerKey, User $user): LlmProvider
    {
        /** @var UserProviderSettings|null $setting */
        $setting = $user->providerSettings()
            ->where('provider_key', $providerKey)
            ->first();

        if ($setting === null || ! ConsensusSlotReadiness::isPresetReady($user, $providerKey)) {
            return $this->disabledProvider($logicalName);
        }

        $aiKey = self::PRESET_AI_KEY[$providerKey] ?? $providerKey;
        $options = ProviderGenerationOptions::sanitize($setting->provider_options);

        return $this->buildProvider($logicalName, new LlmConnectionConfig(
            aiProviderKey: $providerKey === 'ollama' ? 'openai' : $aiKey,
            enabled: true,
            model: $setting->model,
            apiKey: ConsensusSlotReadiness::localApiKey($setting->api_key),
            apiUrl: $setting->api_url !== null
                ? ConsensusSlotReadiness::normalizeOpenAiCompatibleBaseUrl($setting->api_url)
                : null,
            timeout: $this->providerTimeout(),
            providerOptions: $options,
        ));
    }

    private function resolveCustomSlot(string $logicalName, int $customProviderId, User $user): LlmProvider
    {
        /** @var UserCustomProvider|null $custom */
        $custom = $user->customProviders()->find($customProviderId);

        if ($custom === null || ! ConsensusSlotReadiness::isCustomReady($user, $customProviderId)) {
            return $this->disabledProvider($logicalName);
        }

        $options = ProviderGenerationOptions::sanitize($custom->provider_options);

        return $this->buildProvider($logicalName, new LlmConnectionConfig(
            aiProviderKey: 'openai',
            enabled: true,
            model: $custom->model,
            apiKey: ConsensusSlotReadiness::localApiKey($custom->api_key),
            apiUrl: ConsensusSlotReadiness::normalizeOpenAiCompatibleBaseUrl($custom->api_url),
            timeout: $this->providerTimeout(),
            providerOptions: $options,
        ));
    }

    /**
     * @return LlmProvider[]
     */
    private function buildSharedLocalProviders(SystemDemoSettings $settings): array
    {
        $connection = new LlmConnectionConfig(
            aiProviderKey: 'openai',
            enabled: true,
            apiKey: $settings->shared_api_key,
            apiUrl: $settings->shared_api_url,
            timeout: $this->providerTimeout(),
        );

        return array_map(
            fn (string $name) => $this->buildProvider($name, $connection),
            self::SLOTS,
        );
    }

    private function buildProvider(string $logicalName, LlmConnectionConfig $connection): LlmProvider
    {
        return match ($logicalName) {
            'anthropic' => new AnthropicLlmProvider($connection, textProviderFactory: $this->textProviderFactory),
            'gemini' => new GeminiLlmProvider($connection, textProviderFactory: $this->textProviderFactory),
            default => new OpenAiLlmProvider($connection, textProviderFactory: $this->textProviderFactory),
        };
    }

    private function disabledProvider(string $logicalName): LlmProvider
    {
        return $this->buildProvider($logicalName, new LlmConnectionConfig(
            aiProviderKey: match ($logicalName) {
                'anthropic' => 'anthropic',
                'gemini' => 'gemini',
                default => 'openai',
            },
            enabled: false,
            timeout: $this->providerTimeout(),
        ));
    }

    private function envConnection(string $provider): LlmConnectionConfig
    {
        return new LlmConnectionConfig(
            aiProviderKey: $provider,
            enabled: $this->providerEnabled($provider),
            model: $this->providerModel($provider),
            apiKey: $this->config->get("ai.providers.{$provider}.key"),
            apiUrl: $this->config->get("ai.providers.{$provider}.url"),
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
