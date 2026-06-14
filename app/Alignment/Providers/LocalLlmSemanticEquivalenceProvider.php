<?php

namespace App\Alignment\Providers;

use App\Alignment\Contracts\SemanticEquivalenceProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class LocalLlmSemanticEquivalenceProvider implements SemanticEquivalenceProvider
{
    public function __construct(
        private readonly string $apiUrl,
        private readonly string $model,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds,
    ) {}

    public function clusterKeys(array $candidates): array
    {
        $systemPrompt = <<<'PROMPT'
你是 claim key 對齊助手。只判斷 canonical_key 是否語意等價，不要判斷 value 對錯。
輸出 JSON：{"clusters": [{"keys": ["key1", "key2"], "equivalent": true, "confidence": "high|medium|low"}]}
confidence 必須為 high、medium 或 low。不確定時輸出 equivalent: false。
PROMPT;

        $userPayload = json_encode(['candidates' => $candidates], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);

        try {
            $response = Http::withToken($this->apiKey)
                ->baseUrl($this->apiUrl)
                ->timeout($this->timeoutSeconds)
                ->post('/v1/chat/completions', [
                    'model' => $this->model,
                    'response_format' => ['type' => 'json_object'],
                    'messages' => [
                        ['role' => 'system', 'content' => $systemPrompt],
                        ['role' => 'user', 'content' => $userPayload],
                    ],
                ]);

            $content = $response->json('choices.0.message.content') ?? '';
            $decoded = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

            return [
                'clusters' => $decoded['clusters'] ?? [],
                'status' => 'success',
            ];
        } catch (ConnectionException $e) {
            throw new \RuntimeException('LLM connection timeout: '.$e->getMessage(), 0, $e);
        } catch (\JsonException $e) {
            throw new \RuntimeException('LLM returned invalid JSON: '.$e->getMessage(), 0, $e);
        }
    }
}
