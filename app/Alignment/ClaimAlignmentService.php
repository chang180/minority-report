<?php

namespace App\Alignment;

use App\Alignment\Contracts\SemanticEquivalenceProvider;
use App\Alignment\Providers\LocalLlmSemanticEquivalenceProvider;
use App\Alignment\Providers\NullSemanticEquivalenceProvider;
use App\Consensus\Aligner\StringClaimAligner;
use App\Consensus\Contracts\ClaimAligner;
use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ProviderResponse;
use App\Models\SystemAlignerSettings;

class ClaimAlignmentService implements ClaimAligner
{
    private const SEMANTIC_TYPES = ['boolean', 'date', 'number', 'version'];

    public function __construct(
        private readonly StringClaimAligner $stringAligner,
    ) {}

    /** @param ProviderResponse[] $analyzableResponses */
    public function align(array $analyzableResponses): AlignmentResult
    {
        $base = $this->stringAligner->align($analyzableResponses);
        $settings = SystemAlignerSettings::instance();

        if ($settings->mode !== 'semantic_llm' || ! $settings->enabled) {
            return new AlignmentResult(
                aligned: $base->aligned,
                unmatched: $base->unmatched,
                unalignable: $base->unalignable,
                metadata: ['aligner_mode' => 'string'],
            );
        }

        $provider = $this->resolveProvider($settings);

        return $this->runSemanticPass($base, $provider, $settings->min_confidence ?? 'high');
    }

    private function runSemanticPass(
        AlignmentResult $base,
        SemanticEquivalenceProvider $provider,
        string $minConfidence,
    ): AlignmentResult {
        $candidates = $this->buildCandidates($base->unmatched);

        if (empty($candidates)) {
            return new AlignmentResult(
                aligned: $base->aligned,
                unmatched: $base->unmatched,
                unalignable: $base->unalignable,
                metadata: [
                    'aligner_mode' => 'semantic_llm',
                    'semantic_skipped' => true,
                    'fallback_reason' => null,
                    'semantic_clusters' => [],
                ],
            );
        }

        try {
            $result = $provider->clusterKeys($candidates);
        } catch (\Throwable $e) {
            return new AlignmentResult(
                aligned: $base->aligned,
                unmatched: $base->unmatched,
                unalignable: $base->unalignable,
                metadata: [
                    'aligner_mode' => 'string',
                    'semantic_skipped' => false,
                    'fallback_reason' => $e->getMessage(),
                    'semantic_clusters' => [],
                ],
            );
        }

        [$newAligned, $remainingUnmatched] = $this->applySemanticClusters(
            $result['clusters'] ?? [],
            $base->unmatched,
            $minConfidence,
        );

        return new AlignmentResult(
            aligned: array_merge($base->aligned, $newAligned),
            unmatched: $remainingUnmatched,
            unalignable: $base->unalignable,
            metadata: [
                'aligner_mode' => 'semantic_llm',
                'semantic_skipped' => false,
                'fallback_reason' => null,
                'semantic_clusters' => $result['clusters'] ?? [],
            ],
        );
    }

    /**
     * Collect unmatched claims that are eligible for semantic clustering.
     *
     * @param  array<int, array<string, mixed>>  $unmatched
     * @return array<int, array{type: string, provider: string, canonical_key: string, value: string, unit: ?string}>
     */
    private function buildCandidates(array $unmatched): array
    {
        // Group unmatched by type, only process eligible types with ≥2 distinct providers
        $byType = [];
        foreach ($unmatched as $entry) {
            $type = $entry['type'] ?? '';
            if (! in_array($type, self::SEMANTIC_TYPES, true)) {
                continue;
            }
            $byType[$type][] = $entry;
        }

        $candidates = [];
        foreach ($byType as $type => $entries) {
            $providers = array_unique(array_column($entries, 'provider'));
            if (count($providers) < 2) {
                continue;
            }
            foreach ($entries as $entry) {
                $candidates[] = [
                    'type' => $type,
                    'provider' => $entry['provider'],
                    'canonical_key' => $entry['canonical_key'],
                    'value' => $entry['value'] ?? '',
                    'unit' => $entry['unit'] ?? null,
                ];
            }
        }

        return $candidates;
    }

    /**
     * @param  array<int, array{keys: string[], equivalent: bool, confidence: string}>  $clusters
     * @param  array<int, array<string, mixed>>  $unmatched
     * @return array{0: array<int, array<string, mixed>>, 1: array<int, array<string, mixed>>}
     */
    private function applySemanticClusters(array $clusters, array $unmatched, string $minConfidence): array
    {
        $confidenceRank = ['high' => 2, 'medium' => 1, 'low' => 0];
        $minRank = $confidenceRank[$minConfidence] ?? 2;

        // Build a map: canonical_key → representative key for merging
        $keyToRepresentative = [];
        foreach ($clusters as $cluster) {
            if (! ($cluster['equivalent'] ?? false)) {
                continue;
            }
            $confidence = $cluster['confidence'] ?? 'low';
            if (($confidenceRank[$confidence] ?? 0) < $minRank) {
                continue;
            }
            $keys = $cluster['keys'] ?? [];
            if (count($keys) < 2) {
                continue;
            }
            $representative = $keys[0];
            foreach ($keys as $key) {
                $keyToRepresentative[$key] = $representative;
            }
        }

        if (empty($keyToRepresentative)) {
            return [[], $unmatched];
        }

        // Group unmatched entries by their representative key (same type required)
        $groups = [];
        $untouched = [];

        foreach ($unmatched as $entry) {
            $key = $entry['canonical_key'] ?? '';
            $type = $entry['type'] ?? '';
            $rep = $keyToRepresentative[$key] ?? null;

            if ($rep === null) {
                $untouched[] = $entry;

                continue;
            }

            $groupKey = $type.'|'.$rep;
            $groups[$groupKey]['type'] = $type;
            $groups[$groupKey]['canonical_key'] = $rep;
            $groups[$groupKey]['normalized_key'] = $rep;
            $groups[$groupKey]['providers'][$entry['provider']] = [
                'value' => $entry['value'] ?? '',
                'unit' => $entry['unit'] ?? null,
                'canonical_key' => $key,
            ];
        }

        $newAligned = [];
        $stillUnmatched = [];

        foreach ($groups as $group) {
            if (count($group['providers']) >= 2) {
                $newAligned[] = $group;
            } else {
                // Only one provider ended up in this group, keep as unmatched
                $provider = array_key_first($group['providers']);
                $stillUnmatched[] = [
                    'type' => $group['type'],
                    'canonical_key' => $group['canonical_key'],
                    'normalized_key' => $group['normalized_key'],
                    'provider' => $provider,
                    'value' => $group['providers'][$provider]['value'],
                    'unit' => $group['providers'][$provider]['unit'],
                ];
            }
        }

        return [$newAligned, array_values(array_merge($untouched, $stillUnmatched))];
    }

    private function resolveProvider(SystemAlignerSettings $settings): SemanticEquivalenceProvider
    {
        if (filled($settings->local_api_url) && filled($settings->local_model)) {
            return new LocalLlmSemanticEquivalenceProvider(
                apiUrl: $settings->local_api_url,
                model: $settings->local_model,
                apiKey: $settings->local_api_key ?? '',
                timeoutSeconds: $settings->timeout_seconds,
            );
        }

        return new NullSemanticEquivalenceProvider;
    }
}
