<?php

namespace App\Grounding;

use App\Grounding\DTO\GroundingSource;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class WebSearchExecutor
{
    public function __construct(
        private readonly string $provider,
        private readonly ?string $apiKey,
        private readonly ?string $apiUrl = null,
    ) {}

    /**
     * Execute a web search and return sources.
     *
     * @return GroundingSource[]
     */
    public function search(string $query): array
    {
        return match ($this->provider) {
            'tavily' => $this->searchTavily($query),
            'serper' => $this->searchSerper($query),
            'duckduckgo_lite' => $this->searchDuckDuckGoLite($query),
            default => [],
        };
    }

    /**
     * @return GroundingSource[]
     */
    private function searchTavily(string $query): array
    {
        $url = $this->apiUrl ?? 'https://api.tavily.com/search';

        try {
            $response = Http::withToken($this->apiKey ?? '')
                ->timeout(30)
                ->post($url, [
                    'query' => $query,
                    'max_results' => 5,
                ]);

            if (! $response->successful()) {
                return [];
            }

            return array_map(
                fn (array $r) => new GroundingSource(
                    title: $r['title'] ?? '',
                    url: $r['url'] ?? '',
                    snippet: $r['content'] ?? '',
                ),
                $response->json('results') ?? [],
            );
        } catch (ConnectionException) {
            return [];
        }
    }

    /**
     * @return GroundingSource[]
     */
    private function searchSerper(string $query): array
    {
        $url = $this->apiUrl ?? 'https://google.serper.dev/search';

        try {
            $response = Http::withHeaders(['X-API-KEY' => $this->apiKey ?? ''])
                ->timeout(30)
                ->post($url, ['q' => $query]);

            if (! $response->successful()) {
                return [];
            }

            $organic = $response->json('organic') ?? [];

            return array_map(
                fn (array $r) => new GroundingSource(
                    title: $r['title'] ?? '',
                    url: $r['link'] ?? '',
                    snippet: $r['snippet'] ?? '',
                ),
                $organic,
            );
        } catch (ConnectionException) {
            return [];
        }
    }

    /**
     * @return GroundingSource[]
     */
    private function searchDuckDuckGoLite(string $query): array
    {
        $url = $this->apiUrl ?? 'https://api.duckduckgo.com/';

        try {
            $response = Http::timeout(30)
                ->get($url, [
                    'q' => $query,
                    'format' => 'json',
                    'no_html' => 1,
                    'skip_disambig' => 1,
                ]);

            if (! $response->successful()) {
                return [];
            }

            $relatedTopics = $response->json('RelatedTopics') ?? [];
            $sources = [];

            foreach ($relatedTopics as $topic) {
                if (! isset($topic['FirstURL'], $topic['Text'])) {
                    continue;
                }

                $sources[] = new GroundingSource(
                    title: $topic['Text'] ?? '',
                    url: $topic['FirstURL'],
                    snippet: $topic['Text'] ?? '',
                );

                if (count($sources) >= 5) {
                    break;
                }
            }

            return $sources;
        } catch (ConnectionException) {
            return [];
        }
    }
}
