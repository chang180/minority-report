<?php

namespace App\AI\Providers;

use App\Models\User;
use App\Models\UserCustomProvider;
use App\Models\UserProviderSettings;

class ConsensusSlotReadiness
{
    /** Preset keys for self-hosted endpoints — API key optional when URL is set. */
    private const LOCAL_PRESET_KEYS = ['ollama'];

    /**
     * @param  array<string, mixed>|null  $slot
     */
    public static function isSlotReady(User $user, ?array $slot): bool
    {
        if ($slot === null) {
            return false;
        }

        return match ($slot['type'] ?? null) {
            'preset' => self::isPresetReady($user, (string) ($slot['provider_key'] ?? '')),
            'custom' => self::isCustomReady($user, (int) ($slot['custom_provider_id'] ?? 0)),
            default => false,
        };
    }

    public static function isPresetReady(User $user, string $providerKey): bool
    {
        /** @var UserProviderSettings|null $setting */
        $setting = $user->providerSettings()->where('provider_key', $providerKey)->first();

        if ($setting === null || ! $setting->enabled) {
            return false;
        }

        if (in_array($providerKey, self::LOCAL_PRESET_KEYS, true)) {
            return filled($setting->api_url) || filled($setting->api_key);
        }

        return filled($setting->api_key);
    }

    public static function isCustomReady(User $user, int $customProviderId): bool
    {
        /** @var UserCustomProvider|null $custom */
        $custom = $user->customProviders()->find($customProviderId);

        return $custom !== null
            && $custom->enabled
            && filled($custom->api_url);
    }

    public static function localApiKey(?string $apiKey): string
    {
        return filled($apiKey) ? $apiKey : 'local';
    }

    /**
     * OpenAI-compatible clients append `/chat/completions` to the configured base URL.
     */
    public static function normalizeOpenAiCompatibleBaseUrl(string $url): string
    {
        $url = rtrim($url, '/');

        if (str_ends_with($url, '/chat/completions')) {
            return substr($url, 0, -strlen('/chat/completions'));
        }

        return $url;
    }
}
