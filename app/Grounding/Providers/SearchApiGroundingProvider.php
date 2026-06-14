<?php

namespace App\Grounding\Providers;

use App\Grounding\Contracts\GroundingProvider;
use App\Grounding\DTO\GroundingResult;
use App\Grounding\DTO\GroundingSource;
use App\Grounding\WebSearchExecutor;

class SearchApiGroundingProvider implements GroundingProvider
{
    public function __construct(
        private readonly WebSearchExecutor $executor,
        private readonly string $providerName,
    ) {}

    public function fetch(string $questionText): GroundingResult
    {
        $query = $questionText;
        $sources = $this->executor->search($query);

        if (empty($sources)) {
            return new GroundingResult(
                status: 'partial',
                groundingAvailable: false,
                query: $query,
                summary: '',
                sources: [],
                providerMode: 'search_api',
            );
        }

        $summary = $this->buildSummary($sources);

        return new GroundingResult(
            status: 'success',
            groundingAvailable: true,
            query: $query,
            summary: $summary,
            sources: $sources,
            providerMode: 'search_api',
        );
    }

    /**
     * @param  GroundingSource[]  $sources
     */
    private function buildSummary(array $sources): string
    {
        $lines = [];
        foreach (array_slice($sources, 0, 3) as $source) {
            if ($source->snippet !== '') {
                $lines[] = $source->snippet;
            }
        }

        return implode(' ', $lines);
    }
}
