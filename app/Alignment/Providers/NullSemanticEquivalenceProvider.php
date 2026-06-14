<?php

namespace App\Alignment\Providers;

use App\Alignment\Contracts\SemanticEquivalenceProvider;

class NullSemanticEquivalenceProvider implements SemanticEquivalenceProvider
{
    public function clusterKeys(array $candidates): array
    {
        return ['clusters' => [], 'status' => 'skipped'];
    }
}
