<?php

namespace App\Consensus\Extractor;

use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;

class JsonResponseExtractor implements ResponseExtractor
{
    public function __construct(
        private readonly string $extractorModel = 'fixture-json-replay',
        private readonly ClaimStatementParser $claimStatementParser = new ClaimStatementParser,
    ) {}

    public function extract(
        ProviderResponse $providerResponse,
        ClassificationResult $classification,
    ): ProviderResponse {
        $extractionPrompt = $this->buildExtractionPrompt($providerResponse, $classification);

        if ($providerResponse->providerStatus !== 'success') {
            return $this->withExtraction(
                providerResponse: $providerResponse,
                extractionStatus: 'not_started',
                normalized: null,
                extractionPrompt: $extractionPrompt,
            );
        }

        $decoded = $this->decodeJson($providerResponse->rawAnswer);

        if ($decoded === null) {
            return $this->withExtraction(
                providerResponse: $providerResponse,
                extractionStatus: 'invalid_json',
                normalized: null,
                error: ['message' => 'Extractor JSON could not be decoded.'],
                extractionPrompt: $extractionPrompt,
            );
        }

        $normalized = $this->normalizePayload($decoded, $classification);

        if ($normalized === null) {
            return $this->withExtraction(
                providerResponse: $providerResponse,
                extractionStatus: 'extraction_failed',
                normalized: null,
                error: ['message' => 'Extractor JSON did not match the normalized response contract.'],
                extractionPrompt: $extractionPrompt,
            );
        }

        return $this->withExtraction(
            providerResponse: $providerResponse,
            extractionStatus: 'success',
            normalized: $normalized,
            extractionPrompt: $extractionPrompt,
        );
    }

    private function buildExtractionPrompt(
        ProviderResponse $providerResponse,
        ClassificationResult $classification,
    ): string {
        return implode("\n", [
            'Extract one provider response into the normalized consensus JSON contract.',
            'Provider: '.$providerResponse->provider,
            'Answer shape: '.$classification->answerShape,
            'Do not use any other provider answer.',
        ]);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function decodeJson(string $rawAnswer): ?array
    {
        $candidate = trim($rawAnswer);

        if ($candidate === '' || $candidate === '[]') {
            return null;
        }

        if (preg_match('/```(?:json)?\s*(.*?)\s*```/su', $candidate, $matches) === 1) {
            $candidate = trim($matches[1]);
        }

        $decoded = json_decode($candidate, true);

        if (is_array($decoded) && ! array_is_list($decoded)) {
            return $decoded;
        }

        if (preg_match('/\{[\s\S]*\}/u', $candidate, $matches) === 1) {
            $decoded = json_decode($matches[0], true);

            if (is_array($decoded) && ! array_is_list($decoded)) {
                return $decoded;
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function unwrapPayload(array $payload): ?array
    {
        if (isset($payload['normalized']) && is_array($payload['normalized'])) {
            return $payload['normalized'];
        }

        foreach (['response', 'result', 'data', 'output'] as $wrapper) {
            if (isset($payload[$wrapper]) && is_array($payload[$wrapper]) && ! array_is_list($payload[$wrapper])) {
                return $payload[$wrapper];
            }
        }

        if (isset($payload['verifications']) && is_array($payload['verifications'])) {
            $first = $payload['verifications'][0] ?? null;

            if (is_array($first)) {
                if (isset($first['claim']) && is_array($first['claim'])) {
                    return [
                        ...array_diff_key($first, ['claim' => true]),
                        'claims' => [$first['claim']],
                    ];
                }

                return $first;
            }
        }

        return $payload;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function normalizePayload(array $payload, ClassificationResult $classification): ?array
    {
        $normalized = $this->unwrapPayload($payload);

        if (! is_array($normalized)) {
            return null;
        }

        $answerShape = $classification->answerShape;
        $directAnswer = $this->normalizeDirectAnswer($normalized['direct_answer'] ?? null, $answerShape);
        $summary = $this->resolveSummary($normalized, $directAnswer);

        if ($summary === null) {
            return null;
        }

        if ($answerShape === 'discrete' && $directAnswer === 'unknown') {
            $directAnswer = $this->inferDirectAnswerFromSummary($summary);
        }

        $claims = $this->normalizeClaims($normalized['claims'] ?? [], $summary);

        return [
            'answer_shape' => $answerShape,
            'direct_answer' => $directAnswer,
            'summary' => $summary,
            'claims' => $claims,
            'citations' => $this->normalizeCitations($normalized['citations'] ?? []),
        ];
    }

    /**
     * @param  array<string, mixed>  $normalized
     */
    private function resolveSummary(array $normalized, string $directAnswer): ?string
    {
        $summary = $normalized['summary'] ?? null;

        if (is_string($summary) && trim($summary) !== '') {
            return $summary;
        }

        if (is_string($normalized['answer'] ?? null) && trim($normalized['answer']) !== '') {
            return trim($normalized['answer']);
        }

        $claims = $normalized['claims'] ?? [];
        if (is_array($claims)) {
            foreach ($claims as $claim) {
                if (! is_array($claim)) {
                    continue;
                }

                foreach (['assertion', 'statement', 'value', 'text'] as $key) {
                    if (is_string($claim[$key] ?? null) && trim($claim[$key]) !== '') {
                        return trim($claim[$key]);
                    }
                }
            }
        }

        return match ($directAnswer) {
            'yes' => 'Affirmative answer.',
            'no' => 'Negative answer.',
            'unknown' => 'Unable to determine.',
            default => null,
        };
    }

    private function normalizeDirectAnswer(mixed $directAnswer, string $answerShape): string
    {
        if ($answerShape === 'open') {
            return 'not_applicable';
        }

        if ($directAnswer === true) {
            return 'yes';
        }

        if ($directAnswer === false) {
            return 'no';
        }

        if (is_string($directAnswer)) {
            $value = mb_strtolower(trim($directAnswer));

            if (in_array($value, ['yes', 'true', 'y', '是', '對', '对', '正确', '正確', 'correct', 'affirmative'], true)) {
                return 'yes';
            }

            if (in_array($value, ['no', 'false', 'n', '否', '不對', '不对', '错误', '錯誤', 'incorrect', 'negative'], true)) {
                return 'no';
            }

            if (in_array($value, ['unknown', 'uncertain', '不知道', '无法确定', '無法確定', 'unsure'], true)) {
                return 'unknown';
            }

            if ($value === 'not_applicable') {
                return 'unknown';
            }
        }

        return 'unknown';
    }

    private function inferDirectAnswerFromSummary(string $summary): string
    {
        $lower = mb_strtolower(trim($summary));

        if ($lower === 'yes' || $lower === 'no') {
            return $lower;
        }

        if (preg_match('/\b(yes|true|correct|affirmative|是|對|对|正確|正确)\b/u', $lower) === 1) {
            return 'yes';
        }

        if (preg_match('/\b(no|false|incorrect|negative|否|不對|不对|錯誤|错误)\b/u', $lower) === 1) {
            return 'no';
        }

        return 'unknown';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeClaims(mixed $claims, string $summary): array
    {
        if (! is_array($claims)) {
            $claims = [];
        }

        $normalized = [];

        foreach ($claims as $claim) {
            if (is_string($claim)) {
                $parsed = $this->claimStatementParser->parseNumericStatement($claim)
                    ?? $this->claimStatementParser->statementClaim($claim);
                $normalized[] = $this->finalizeClaim($parsed);

                continue;
            }

            if (! is_array($claim)) {
                continue;
            }

            $normalized[] = $this->finalizeClaim([
                'type' => $this->normalizeClaimType($claim['type'] ?? null),
                'canonical_key' => $this->canonicalKey($claim),
                'subject' => $this->claimField($claim, ['subject', 'entity', 'topic']),
                'predicate' => $this->claimField($claim, ['predicate', 'property', 'relation']),
                'value' => $this->claimField($claim, ['value', 'assertion', 'statement', 'text', 'description']),
                'unit' => is_scalar($claim['unit'] ?? null) ? (string) $claim['unit'] : null,
                'source' => is_scalar($claim['source'] ?? null) ? (string) $claim['source'] : null,
            ]);
        }

        if ($normalized === []) {
            $fromSummary = $this->claimStatementParser->parseNumericStatement($summary);

            if ($fromSummary !== null) {
                $normalized[] = $this->finalizeClaim($fromSummary);
            }
        }

        return array_values($normalized);
    }

    /**
     * @param  array{type: string, canonical_key: string, subject: string, predicate: string, value: string, unit: ?string, source: ?string}  $claim
     * @return array<string, mixed>
     */
    private function finalizeClaim(array $claim): array
    {
        if ($claim['type'] === 'statement' && $claim['value'] !== '') {
            $numeric = $this->claimStatementParser->parseNumericStatement($claim['value']);

            if ($numeric !== null) {
                $claim = $numeric;
            }
        }

        if ($claim['type'] === 'number' && ($claim['unit'] === null || $claim['unit'] === '') && $claim['value'] !== '') {
            $claim['unit'] = '°C';
        }

        return $claim;
    }

    /**
     * @param  array<string, mixed>  $claim
     * @param  array<int, string>  $keys
     */
    private function claimField(array $claim, array $keys): string
    {
        foreach ($keys as $key) {
            if (is_scalar($claim[$key] ?? null) && (string) $claim[$key] !== '') {
                return (string) $claim[$key];
            }
        }

        return '';
    }

    private function normalizeClaimType(mixed $type): string
    {
        return in_array($type, ['boolean', 'date', 'number', 'version', 'entity', 'source', 'statement'], true)
            ? $type
            : 'statement';
    }

    /**
     * @param  array<string, mixed>  $claim
     */
    private function canonicalKey(array $claim): string
    {
        if (is_string($claim['canonical_key'] ?? null) && trim($claim['canonical_key']) !== '') {
            return $this->canonicalize($claim['canonical_key']);
        }

        return $this->canonicalize(implode(' ', array_filter([
            is_scalar($claim['subject'] ?? null) ? (string) $claim['subject'] : '',
            is_scalar($claim['predicate'] ?? null) ? (string) $claim['predicate'] : '',
        ])));
    }

    private function canonicalize(string $value): string
    {
        $value = mb_strtolower($value);
        $value = preg_replace('/[^\pL\pN]+/u', ' ', $value) ?? '';

        return trim(preg_replace('/\s+/u', ' ', $value) ?? '');
    }

    /**
     * @return array<int, mixed>
     */
    private function normalizeCitations(mixed $citations): array
    {
        return is_array($citations) ? array_values($citations) : [];
    }

    /**
     * @param  array<string, mixed>|null  $normalized
     * @param  array<string, mixed>|null  $error
     */
    private function withExtraction(
        ProviderResponse $providerResponse,
        string $extractionStatus,
        ?array $normalized,
        string $extractionPrompt,
        ?array $error = null,
    ): ProviderResponse {
        return new ProviderResponse(
            provider: $providerResponse->provider,
            model: $providerResponse->model,
            providerStatus: $providerResponse->providerStatus,
            extractionStatus: $extractionStatus,
            rawAnswer: $providerResponse->rawAnswer,
            normalized: $normalized,
            usage: $providerResponse->usage,
            error: $error ?? $providerResponse->error,
            metadata: $providerResponse->metadata,
            extractionPrompt: $extractionPrompt,
            extractorModel: $this->extractorModel,
        );
    }
}
