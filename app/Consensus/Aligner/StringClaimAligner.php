<?php

namespace App\Consensus\Aligner;

use App\Consensus\Contracts\ClaimAligner;
use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ProviderResponse;

class StringClaimAligner implements ClaimAligner
{
    private const STOPWORDS = [
        'the', 'a', 'an', 'is', 'are', 'was', 'were', 'of', 'in', 'on',
        'at', 'to', 'for', 'and', 'or', 'be', 'by', 'as', 'it', 'its',
    ];

    /** @param ProviderResponse[] $analyzableResponses */
    public function align(array $analyzableResponses): AlignmentResult
    {
        // Collect all claims grouped by [type][normalized_key] => [provider => claim]
        $groups = [];

        foreach ($analyzableResponses as $response) {
            $claims = $response->normalized['claims'] ?? [];
            foreach ($claims as $claim) {
                $type = $claim['type'] ?? '';
                $key = $claim['canonical_key'] ?? '';
                $normKey = $this->normalizeKey($key);
                $groupKey = $type.'|'.$normKey;
                $groups[$groupKey]['type'] = $type;
                $groups[$groupKey]['canonical_key'] = $key;
                $groups[$groupKey]['normalized_key'] = $normKey;
                $groups[$groupKey]['providers'][$response->provider] = [
                    'value' => $claim['value'] ?? '',
                    'unit' => $claim['unit'] ?? null,
                    'subject' => $claim['subject'] ?? '',
                    'predicate' => $claim['predicate'] ?? '',
                    'source' => $claim['source'] ?? null,
                    'canonical_key' => $key,
                ];
            }
        }

        $aligned = [];
        $unmatched = [];
        $unalignable = [];

        foreach ($groups as $entry) {
            $providerCount = count($entry['providers']);

            if ($providerCount < 2) {
                // Only one provider has this claim
                $provider = array_key_first($entry['providers']);
                $unmatched[] = [
                    'type' => $entry['type'],
                    'canonical_key' => $entry['canonical_key'],
                    'normalized_key' => $entry['normalized_key'],
                    'provider' => $provider,
                    'value' => $entry['providers'][$provider]['value'],
                    'unit' => $entry['providers'][$provider]['unit'],
                ];

                continue;
            }

            // For number claims with incompatible units, mark as unalignable
            if ($entry['type'] === 'number' && ! $this->unitsAreCompatible($entry['providers'])) {
                $unalignable[] = [
                    'type' => $entry['type'],
                    'canonical_key' => $entry['canonical_key'],
                    'normalized_key' => $entry['normalized_key'],
                    'providers' => $entry['providers'],
                    'reason' => 'incompatible_units',
                ];

                continue;
            }

            $aligned[] = [
                'type' => $entry['type'],
                'canonical_key' => $entry['canonical_key'],
                'normalized_key' => $entry['normalized_key'],
                'providers' => $entry['providers'],
            ];
        }

        return new AlignmentResult(
            aligned: $aligned,
            unmatched: $unmatched,
            unalignable: $unalignable,
        );
    }

    private function normalizeKey(string $key): string
    {
        $key = strtolower($key);
        $key = preg_replace('/[^\w\s]/', ' ', $key) ?? $key;
        $key = preg_replace('/\s+/', ' ', $key) ?? $key;
        $key = trim($key);

        $words = explode(' ', $key);
        $words = array_values(array_filter($words, fn ($w) => $w !== '' && ! in_array($w, self::STOPWORDS, true)));

        return implode(' ', $words);
    }

    /** @param array<string, array{unit: ?string}> $providers */
    private function unitsAreCompatible(array $providers): bool
    {
        $units = array_unique(array_map(fn ($p) => $p['unit'] ?? '', $providers));

        return count($units) === 1;
    }
}
