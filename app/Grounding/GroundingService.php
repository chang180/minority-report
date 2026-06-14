<?php

namespace App\Grounding;

use App\Grounding\Contracts\GroundingProvider;
use App\Grounding\DTO\GroundingResult;
use App\Grounding\Providers\DisabledGroundingProvider;
use App\Grounding\Providers\LocalLlmWebSearchGroundingProvider;
use App\Grounding\Providers\SearchApiGroundingProvider;
use App\Models\SystemGroundingSettings;

class GroundingService
{
    public function fetch(string $questionText, bool $requiresGrounding): GroundingResult
    {
        if (! $requiresGrounding) {
            return GroundingResult::skipped($questionText);
        }

        return $this->resolveProvider()->fetch($questionText);
    }

    private function resolveProvider(): GroundingProvider
    {
        $settings = SystemGroundingSettings::instance();

        if (! $settings->enabled || $settings->mode === 'disabled') {
            return new DisabledGroundingProvider;
        }

        return match ($settings->mode) {
            'local_llm_tool_loop' => $this->buildLocalLlmProvider($settings),
            'search_api' => $this->buildSearchApiProvider($settings),
            default => new DisabledGroundingProvider,
        };
    }

    private function buildLocalLlmProvider(SystemGroundingSettings $settings): LocalLlmWebSearchGroundingProvider
    {
        $executor = new WebSearchExecutor(
            provider: $settings->search_provider ?? 'duckduckgo_lite',
            apiKey: $settings->search_api_key,
            apiUrl: $settings->search_api_url,
        );

        return new LocalLlmWebSearchGroundingProvider(
            apiUrl: $settings->local_api_url ?? config('services.openai.url', 'http://localhost:8080'),
            model: $settings->local_model ?? config('services.openai.model', 'default'),
            apiKey: $settings->local_api_key ?? 'local',
            executor: $executor,
            maxToolRounds: $settings->max_tool_rounds,
            timeoutSeconds: $settings->timeout_seconds,
        );
    }

    private function buildSearchApiProvider(SystemGroundingSettings $settings): SearchApiGroundingProvider
    {
        $executor = new WebSearchExecutor(
            provider: $settings->search_provider ?? 'duckduckgo_lite',
            apiKey: $settings->search_api_key,
            apiUrl: $settings->search_api_url,
        );

        return new SearchApiGroundingProvider(
            executor: $executor,
            providerName: $settings->search_provider ?? 'duckduckgo_lite',
        );
    }
}
