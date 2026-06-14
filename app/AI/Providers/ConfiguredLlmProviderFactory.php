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

        // fake_fixtures mode — callers should use ConsensusDemoFixtureCatalog instead,
        // but return disabled providers here as a safe fallback.
        return [
            $this->disabledProvider('openai'),
            $this->disabledProvider('anthropic'),
            $this->disabledProvider('gemini'),
        ];
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

        if ($setting === null || ! $setting->enabled || empty($setting->api_key)) {
            return $this->disabledProvider($logicalName);
        }

        $aiKey = self::PRESET_AI_KEY[$providerKey] ?? $providerKey;
        $inner = $this->buildPresetInner($providerKey, $setting->model, $this->providerTimeout());

        $overrides = ["ai.providers.{$aiKey}.key" => $setting->api_key];
        if ($setting->api_url !== null) {
            $overrides["ai.providers.{$aiKey}.url"] = $setting->api_url;
        }

        return new ScopedConfigLlmProvider($logicalName, $inner, $overrides, $this->config);
    }

    private function resolveCustomSlot(string $logicalName, int $customProviderId, User $user): LlmProvider
    {
        /** @var UserCustomProvider|null $custom */
        $custom = $user->customProviders()->find($customProviderId);

        if ($custom === null || ! $custom->enabled || empty($custom->api_key)) {
            return $this->disabledProvider($logicalName);
        }

        // Custom providers use the OpenAI-compatible driver.
        $inner = new OpenAiLlmProvider(
            enabled: true,
            model: $custom->model,
            timeout: $this->providerTimeout(),
        );

        return new ScopedConfigLlmProvider($logicalName, $inner, [
            'ai.providers.openai.key' => $custom->api_key,
            'ai.providers.openai.url' => $custom->api_url,
        ], $this->config);
    }

    /**
     * @return LlmProvider[]
     */
    private function buildSharedLocalProviders(SystemDemoSettings $settings): array
    {
        $inner = new OpenAiLlmProvider(enabled: true, timeout: $this->providerTimeout());
        $overrides = ['ai.providers.openai.url' => $settings->shared_api_url];
        if ($settings->shared_api_key !== null) {
            $overrides['ai.providers.openai.key'] = $settings->shared_api_key;
        }

        return array_map(
            fn (string $name) => new ScopedConfigLlmProvider($name, $inner, $overrides, $this->config),
            self::SLOTS,
        );
    }

    private function buildPresetInner(string $providerKey, ?string $model, int $timeout): LaravelAiLlmProvider
    {
        return match ($providerKey) {
            'anthropic' => new AnthropicLlmProvider(enabled: true, model: $model, timeout: $timeout),
            'gemini' => new GeminiLlmProvider(enabled: true, model: $model, timeout: $timeout),
            default => new OpenAiLlmProvider(enabled: true, model: $model, timeout: $timeout),
        };
    }

    private function disabledProvider(string $logicalName): LlmProvider
    {
        $inner = match ($logicalName) {
            'anthropic' => new AnthropicLlmProvider(enabled: false),
            'gemini' => new GeminiLlmProvider(enabled: false),
            default => new OpenAiLlmProvider(enabled: false),
        };

        return new ScopedConfigLlmProvider($logicalName, $inner, [], $this->config);
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
