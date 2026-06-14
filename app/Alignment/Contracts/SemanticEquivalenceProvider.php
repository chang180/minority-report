<?php

namespace App\Alignment\Contracts;

interface SemanticEquivalenceProvider
{
    /**
     * @param  array<int, array{type: string, provider: string, canonical_key: string, value: string, unit: ?string}>  $candidates
     * @return array{clusters: array<int, array{keys: string[], equivalent: bool, confidence: string}>, status: string}
     */
    public function clusterKeys(array $candidates): array;
}
