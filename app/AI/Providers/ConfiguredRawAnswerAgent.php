<?php

namespace App\AI\Providers;

use Illuminate\Contracts\JsonSchema\JsonSchema;
use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Contracts\HasProviderOptions;
use Laravel\Ai\Contracts\HasStructuredOutput;
use Laravel\Ai\Enums\Lab;
use Laravel\Ai\Promptable;

class ConfiguredRawAnswerAgent implements Agent, HasProviderOptions, HasStructuredOutput
{
    use Promptable;

    /** @param  array<string, mixed>  $providerOptions */
    public function __construct(
        private readonly array $providerOptions = [],
    ) {}

    public function instructions(): string
    {
        return implode("\n", [
            'You are a consensus verification provider.',
            'Return JSON matching the schema exactly.',
            'Use only these top-level keys: direct_answer, summary, claims, citations.',
            'For yes/no questions, direct_answer must be yes, no, or unknown (lowercase strings).',
            'summary must be one complete sentence.',
            'claims and citations may be empty arrays.',
            'Preserve uncertainty and avoid fabricating sources.',
        ]);
    }

    public function schema(JsonSchema $schema): array
    {
        return [
            'direct_answer' => $schema->string()
                ->enum(['yes', 'no', 'unknown', 'not_applicable'])
                ->required(),
            'summary' => $schema->string()->required(),
            'claims' => $schema->array()->items(
                $schema->object([
                    'type' => $schema->string()
                        ->enum(['boolean', 'date', 'number', 'version', 'entity', 'source', 'statement']),
                    'canonical_key' => $schema->string(),
                    'subject' => $schema->string(),
                    'predicate' => $schema->string(),
                    'value' => $schema->string(),
                    'unit' => $schema->string(),
                    'source' => $schema->string(),
                ]),
            )->required(),
            'citations' => $schema->array()->items($schema->string())->required(),
        ];
    }

    public function maxTokens(): ?int
    {
        $value = $this->providerOptions['max_tokens'] ?? null;

        return is_numeric($value) ? (int) $value : null;
    }

    public function providerOptions(Lab|string $provider): array
    {
        return $this->providerOptions;
    }
}
