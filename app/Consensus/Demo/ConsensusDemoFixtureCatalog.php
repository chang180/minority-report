<?php

namespace App\Consensus\Demo;

use App\Consensus\Contracts\FakeProviderRegistry;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\Exceptions\ProviderTimeoutException;
use InvalidArgumentException;

class ConsensusDemoFixtureCatalog
{
    public function __construct(
        private readonly FakeProviderRegistry $registry,
    ) {}

    /**
     * @return array<int, array<string, mixed>>
     */
    public function options(): array
    {
        return collect($this->fixtures())
            ->map(fn (array $fixture): array => [
                'id' => $fixture['id'],
                'label' => $fixture['label'],
                'description' => $fixture['description'],
                'sample_question' => $fixture['sample_question'],
                'expected_consensus' => $fixture['expected_consensus'],
                'expected_trust' => $fixture['expected_trust'],
            ])
            ->values()
            ->all();
    }

    /**
     * @return array<int, string>
     */
    public function ids(): array
    {
        return array_column($this->fixtures(), 'id');
    }

    public function defaultFixtureId(): string
    {
        return 'M6-F02';
    }

    /**
     * @return array<string, mixed>
     */
    public function metadataFor(string $fixtureId): array
    {
        $fixture = $this->fixture($fixtureId);

        return [
            'classification' => [
                'type' => $fixture['type'],
                'answer_shape' => $fixture['answer_shape'],
                'requires_grounding' => $fixture['type'] === 'C',
                'classifier_confidence' => 'high',
            ],
            'fixture_id' => $fixture['id'],
            'demo_label' => $fixture['label'],
            'grounding_available' => false,
        ];
    }

    /**
     * @return LlmProvider[]
     */
    public function providersFor(string $fixtureId): array
    {
        $fixture = $this->fixture($fixtureId);

        return array_map(function (array $spec) use ($fixture): LlmProvider {
            $this->registry->register($spec['provider'], $this->providerBehavior($spec, $fixture['answer_shape']));

            return $this->registry->create($spec['provider']);
        }, $fixture['providers']);
    }

    /**
     * @return array<string, mixed>
     */
    private function fixture(string $fixtureId): array
    {
        return collect($this->fixtures())->firstWhere('id', $fixtureId)
            ?? throw new InvalidArgumentException("Unknown demo fixture [{$fixtureId}].");
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function fixtures(): array
    {
        return [
            $this->fixtureDefinition(
                id: 'M6-F01',
                label: '完全一致',
                description: '三席皆回答「是」，且未偵測到重大主張衝突。',
                sampleQuestion: '水的沸點在海平面是否為攝氏 100 度？',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'yes'],
                expectedConsensus: 'Full',
                expectedTrust: 'High',
            ),
            $this->fixtureDefinition(
                id: 'M6-F02',
                label: '少數意見報告',
                description: '兩席回答「是」，Gemini 持不同意見（2 對 1）。',
                sampleQuestion: '產品發布日期是否通過共識驗證？',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'no'],
                expectedConsensus: 'Majority',
                expectedTrust: 'Medium',
            ),
            $this->fixtureDefinition(
                id: 'M6-F07',
                label: '主張衝突',
                description: '直接回答一致，但 Gemini 的發布日期主張與其他兩席不同。',
                sampleQuestion: '產品是否已於 2024 年 3 月 15 日正式發布？',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'yes'],
                expectedConsensus: 'Majority',
                expectedTrust: 'Low',
                claims: [
                    'openai' => [$this->claim('date', 'launch date', '2024-03-15')],
                    'anthropic' => [$this->claim('date', 'launch date', '2024-03-15')],
                    'gemini' => [$this->claim('date', 'launch date', '2023-06-01')],
                ],
            ),
            $this->fixtureDefinition(
                id: 'M6-F10',
                label: '證據不足',
                description: '抽取失敗與逾時後，僅剩一席可分析。',
                sampleQuestion: '此套件是否仍由官方維護？',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'unknown'],
                expectedConsensus: 'Insufficient',
                expectedTrust: 'Unknown',
                modes: ['openai' => 'success', 'anthropic' => 'invalid_json', 'gemini' => 'timeout'],
            ),
            $this->fixtureDefinition(
                id: 'M6-F14',
                label: '無共識',
                description: '多個衝突軸指向不同 provider，無法判定單一少數方。',
                sampleQuestion: '產品發布時間是否已確定為 2024 年 3 月？',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'no'],
                expectedConsensus: 'None',
                expectedTrust: 'Low',
                claims: [
                    'openai' => [$this->claim('date', 'launch date', '2024-03')],
                    'anthropic' => [$this->claim('date', 'launch date', '2023-01')],
                    'gemini' => [$this->claim('date', 'launch date', '2024-03')],
                ],
            ),
            $this->fixtureDefinition(
                id: 'M8-F16',
                label: '語意鍵對齊',
                description: '三席回報相同日期，但 canonical_key 不同；字串對齊無法合併，語意對齊可合併。',
                sampleQuestion: '產品正式發布日期是否為 2024 年 3 月 15 日？',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'yes'],
                expectedConsensus: 'Full',
                expectedTrust: 'High',
                claims: [
                    'openai' => [$this->claim('date', 'release date', '2024-03-15')],
                    'anthropic' => [$this->claim('date', 'product launch date', '2024-03-15')],
                    'gemini' => [$this->claim('date', 'official launch date', '2024-03-15')],
                ],
            ),
        ];
    }

    /**
     * @param  array<string, string>  $directAnswers
     * @param  array<string, string>  $modes
     * @param  array<string, array<int, array<string, mixed>>>  $claims
     * @return array<string, mixed>
     */
    private function fixtureDefinition(
        string $id,
        string $label,
        string $description,
        string $sampleQuestion,
        array $directAnswers,
        string $expectedConsensus,
        string $expectedTrust,
        array $modes = [],
        array $claims = [],
    ): array {
        return [
            'id' => $id,
            'label' => $label,
            'description' => $description,
            'sample_question' => $sampleQuestion,
            'type' => 'B',
            'answer_shape' => 'discrete',
            'expected_consensus' => $expectedConsensus,
            'expected_trust' => $expectedTrust,
            'providers' => array_map(
                fn (string $provider): array => [
                    'provider' => $provider,
                    'mode' => $modes[$provider] ?? 'success',
                    'direct_answer' => $directAnswers[$provider],
                    'summary' => $this->summary($provider, $directAnswers[$provider]),
                    'claims' => $claims[$provider] ?? [],
                ],
                ['openai', 'anthropic', 'gemini'],
            ),
        ];
    }

    /**
     * @param  array<string, mixed>  $spec
     */
    private function providerBehavior(array $spec, string $answerShape): \Closure
    {
        return function (Question $question, string $prompt) use ($spec, $answerShape): ProviderResponse {
            if ($spec['mode'] === 'timeout') {
                throw new ProviderTimeoutException('示範範例 provider 呼叫逾時。');
            }

            if ($spec['mode'] === 'invalid_json') {
                return new ProviderResponse(
                    provider: $spec['provider'],
                    model: 'm6-demo-fixture',
                    providerStatus: 'success',
                    extractionStatus: 'not_started',
                    rawAnswer: 'not-json',
                );
            }

            return new ProviderResponse(
                provider: $spec['provider'],
                model: 'm6-demo-fixture',
                providerStatus: 'success',
                extractionStatus: 'not_started',
                rawAnswer: json_encode([
                    'answer_shape' => $answerShape,
                    'direct_answer' => $spec['direct_answer'],
                    'summary' => $spec['summary'],
                    'claims' => $spec['claims'],
                    'citations' => [],
                ], JSON_THROW_ON_ERROR),
                usage: ['input_tokens' => 0, 'output_tokens' => 0],
                metadata: ['demo_fixture' => true],
            );
        };
    }

    /**
     * @return array<string, mixed>
     */
    private function claim(string $type, string $key, string $value, ?string $unit = null): array
    {
        return [
            'type' => $type,
            'canonical_key' => $key,
            'subject' => $key,
            'predicate' => 'is',
            'value' => $value,
            'unit' => $unit,
            'source' => null,
        ];
    }

    private function summary(string $provider, string $directAnswer): string
    {
        $seat = match ($provider) {
            'openai' => 'OpenAI 席',
            'anthropic' => 'Anthropic 席',
            'gemini' => 'Gemini 席',
            default => $provider,
        };

        return match ($directAnswer) {
            'yes' => "{$seat}認為該說法成立。",
            'no' => "{$seat}認為該說法不成立。",
            default => "{$seat}無法判定。",
        };
    }
}
