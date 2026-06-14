<?php

namespace App\Consensus;

use App\Models\ProviderResponse;
use Illuminate\Support\Collection;

final class ProviderResponseCatalog
{
    /** @var list<string> */
    public const SLOT_ORDER = ['openai', 'anthropic', 'gemini'];

    /**
     * @return Collection<int, ProviderResponse>
     */
    public static function latestForVerification(int $verificationRequestId): Collection
    {
        $latestIds = ProviderResponse::query()
            ->where('verification_request_id', $verificationRequestId)
            ->selectRaw('MAX(id) as id')
            ->groupBy('provider')
            ->pluck('id');

        if ($latestIds->isEmpty()) {
            return collect();
        }

        $responses = ProviderResponse::query()
            ->whereIn('id', $latestIds)
            ->get();

        return $responses
            ->sortBy(fn (ProviderResponse $response): int => self::slotIndex($response->provider))
            ->values();
    }

    public static function slotIndex(string $provider): int
    {
        $index = array_search($provider, self::SLOT_ORDER, true);

        return $index === false ? 99 : $index;
    }
}
