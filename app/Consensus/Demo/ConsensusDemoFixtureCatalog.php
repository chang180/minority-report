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
                label: 'Full consensus',
                description: 'All providers answer yes with no detected major conflict.',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'yes'],
                expectedConsensus: 'Full',
                expectedTrust: 'High',
            ),
            $this->fixtureDefinition(
                id: 'M6-F02',
                label: 'Minority report',
                description: 'Two providers answer yes and Gemini disagrees.',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'no'],
                expectedConsensus: 'Majority',
                expectedTrust: 'Medium',
            ),
            $this->fixtureDefinition(
                id: 'M6-F07',
                label: 'Claim conflict',
                description: 'Direct answers align, but Gemini reports a conflicting launch date.',
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
                label: 'Insufficient',
                description: 'Only one provider is analyzable after extractor and timeout failures.',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'unknown'],
                expectedConsensus: 'Insufficient',
                expectedTrust: 'Unknown',
                modes: ['openai' => 'success', 'anthropic' => 'invalid_json', 'gemini' => 'timeout'],
            ),
            $this->fixtureDefinition(
                id: 'M6-F14',
                label: 'No consensus',
                description: 'Multiple conflict axes point to different providers, so no majority is reported.',
                directAnswers: ['openai' => 'yes', 'anthropic' => 'yes', 'gemini' => 'no'],
                expectedConsensus: 'None',
                expectedTrust: 'Low',
                claims: [
                    'openai' => [$this->claim('date', 'launch date', '2024-03')],
                    'anthropic' => [$this->claim('date', 'launch date', '2023-01')],
                    'gemini' => [$this->claim('date', 'launch date', '2024-03')],
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
                throw new ProviderTimeoutException('Demo fixture provider timed out.');
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
        return match ($directAnswer) {
            'yes' => "{$provider} says the claim is supported.",
            'no' => "{$provider} says the claim is not supported.",
            default => "{$provider} cannot determine the claim.",
        };
    }
}
