<?php

namespace App\Consensus\Extractor;

use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;

class JsonResponseExtractor implements ResponseExtractor
{
    public function __construct(
        private readonly string $extractorModel = 'fixture-json-replay',
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

        if (preg_match('/^```(?:json)?\s*(.*?)\s*```$/su', $candidate, $matches) === 1) {
            $candidate = trim($matches[1]);
        }

        $decoded = json_decode($candidate, true);

        if (is_array($decoded)) {
            return $decoded;
        }

        $objectStart = mb_strpos($candidate, '{');
        $objectEnd = mb_strrpos($candidate, '}');

        if ($objectStart === false || $objectEnd === false || $objectEnd <= $objectStart) {
            return null;
        }

        $decoded = json_decode(mb_substr($candidate, $objectStart, $objectEnd - $objectStart + 1), true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>|null
     */
    private function normalizePayload(array $payload, ClassificationResult $classification): ?array
    {
        $normalized = $payload['normalized'] ?? $payload;

        if (! is_array($normalized)) {
            return null;
        }

        $answerShape = $classification->answerShape;
        $directAnswer = $this->normalizeDirectAnswer($normalized['direct_answer'] ?? null, $answerShape);
        $summary = $normalized['summary'] ?? null;

        if (! is_string($summary)) {
            return null;
        }

        return [
            'answer_shape' => $answerShape,
            'direct_answer' => $directAnswer,
            'summary' => $summary,
            'claims' => $this->normalizeClaims($normalized['claims'] ?? []),
            'citations' => $this->normalizeCitations($normalized['citations'] ?? []),
        ];
    }

    private function normalizeDirectAnswer(mixed $directAnswer, string $answerShape): string
    {
        if ($answerShape === 'open') {
            return 'not_applicable';
        }

        return in_array($directAnswer, ['yes', 'no', 'unknown'], true)
            ? $directAnswer
            : 'unknown';
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function normalizeClaims(mixed $claims): array
    {
        if (! is_array($claims)) {
            return [];
        }

        return array_values(array_map(
            fn (array $claim): array => [
                'type' => $this->normalizeClaimType($claim['type'] ?? null),
                'canonical_key' => $this->canonicalKey($claim),
                'subject' => is_string($claim['subject'] ?? null) ? $claim['subject'] : '',
                'predicate' => is_string($claim['predicate'] ?? null) ? $claim['predicate'] : '',
                'value' => is_scalar($claim['value'] ?? null) ? (string) $claim['value'] : '',
                'unit' => is_scalar($claim['unit'] ?? null) ? (string) $claim['unit'] : null,
                'source' => is_scalar($claim['source'] ?? null) ? (string) $claim['source'] : null,
            ],
            array_filter($claims, 'is_array'),
        ));
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
