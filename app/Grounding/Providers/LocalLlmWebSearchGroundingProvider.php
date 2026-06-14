<?php

namespace App\Grounding\Providers;

use App\Grounding\Contracts\GroundingProvider;
use App\Grounding\DTO\GroundingResult;
use App\Grounding\DTO\GroundingSource;
use App\Grounding\WebSearchExecutor;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LocalLlmWebSearchGroundingProvider implements GroundingProvider
{
    private const WEB_SEARCH_TOOL = [
        'type' => 'function',
        'function' => [
            'name' => 'web_search',
            'description' => 'Search the web for current information on the given query.',
            'parameters' => [
                'type' => 'object',
                'properties' => [
                    'query' => ['type' => 'string', 'description' => 'The search query'],
                ],
                'required' => ['query'],
            ],
        ],
    ];

    public function __construct(
        private readonly string $apiUrl,
        private readonly string $model,
        private readonly string $apiKey,
        private readonly WebSearchExecutor $executor,
        private readonly int $maxToolRounds,
        private readonly int $timeoutSeconds,
    ) {}

    public function fetch(string $questionText): GroundingResult
    {
        $query = $questionText;
        $startMs = (int) (microtime(true) * 1000);

        $messages = [
            [
                'role' => 'system',
                'content' => 'You are a research assistant. Use the web_search tool to find current information to answer the user\'s question. After searching, provide a concise summary of what you found.',
            ],
            [
                'role' => 'user',
                'content' => "Research this question and summarize what current sources say: {$questionText}",
            ],
        ];

        /** @var GroundingSource[] $allSources */
        $allSources = [];
        $finalAnswer = '';
        $toolRounds = 0;

        try {
            for ($round = 0; $round < $this->maxToolRounds; $round++) {
                $response = Http::withToken($this->apiKey)
                    ->timeout($this->timeoutSeconds)
                    ->post(rtrim($this->apiUrl, '/').'/v1/chat/completions', [
                        'model' => $this->model,
                        'messages' => $messages,
                        'tools' => [self::WEB_SEARCH_TOOL],
                        'tool_choice' => 'auto',
                    ]);

                if (! $response->successful()) {
                    break;
                }

                $choice = $response->json('choices.0') ?? [];
                $message = $choice['message'] ?? [];
                $finishReason = $choice['finish_reason'] ?? 'stop';

                $messages[] = $message;

                if ($finishReason !== 'tool_calls') {
                    $finalAnswer = $message['content'] ?? '';
                    break;
                }

                $toolCalls = $message['tool_calls'] ?? [];
                $toolRounds++;

                foreach ($toolCalls as $toolCall) {
                    $functionName = $toolCall['function']['name'] ?? '';

                    if ($functionName !== 'web_search') {
                        continue;
                    }

                    $args = json_decode($toolCall['function']['arguments'] ?? '{}', true) ?? [];
                    $searchQuery = $args['query'] ?? $questionText;

                    $sources = $this->executor->search($searchQuery);
                    $allSources = array_merge($allSources, $sources);

                    $toolResultContent = empty($sources)
                        ? 'No results found.'
                        : implode("\n\n", array_map(
                            fn (GroundingSource $s) => "Title: {$s->title}\nURL: {$s->url}\nSnippet: {$s->snippet}",
                            $sources,
                        ));

                    $messages[] = [
                        'role' => 'tool',
                        'tool_call_id' => $toolCall['id'] ?? '',
                        'content' => $toolResultContent,
                    ];
                }
            }
        } catch (ConnectionException $e) {
            return GroundingResult::failed($query, 'local_llm_tool_loop', $e->getMessage());
        }

        $durationMs = (int) (microtime(true) * 1000) - $startMs;

        $uniqueSources = $this->deduplicateSources($allSources);

        if (empty($uniqueSources) || $finalAnswer === '') {
            $status = empty($uniqueSources) ? 'partial' : 'partial';

            return new GroundingResult(
                status: $status,
                groundingAvailable: false,
                query: $query,
                summary: $finalAnswer,
                sources: $uniqueSources,
                providerMode: 'local_llm_tool_loop',
                metadata: ['tool_rounds' => $toolRounds, 'duration_ms' => $durationMs],
            );
        }

        return new GroundingResult(
            status: 'success',
            groundingAvailable: true,
            query: $query,
            summary: $finalAnswer,
            sources: $uniqueSources,
            providerMode: 'local_llm_tool_loop',
            metadata: ['tool_rounds' => $toolRounds, 'duration_ms' => $durationMs],
        );
    }

    /**
     * @param  GroundingSource[]  $sources
     * @return GroundingSource[]
     */
    private function deduplicateSources(array $sources): array
    {
        $seen = [];
        $unique = [];

        foreach ($sources as $source) {
            if (! isset($seen[$source->url])) {
                $seen[$source->url] = true;
                $unique[] = $source;
            }
        }

        return $unique;
    }
}
