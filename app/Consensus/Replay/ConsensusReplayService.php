<?php

namespace App\Consensus\Replay;

use App\Consensus\ConsensusWorkflow;
use App\Consensus\ProviderResponseCatalog;
use App\Models\ProviderResponse;
use App\Models\VerificationRequest;

class ConsensusReplayService
{
    public function __construct(
        private readonly ConsensusWorkflow $workflow,
    ) {}

    public function replayRequest(int $verificationRequestId): VerificationRequest
    {
        $source = $this->sourceRequest($verificationRequestId);
        $replay = VerificationRequest::create([
            'question' => $source->question,
            'classified_type' => $source->classified_type,
            'classifier_confidence' => $source->classifier_confidence,
            'answer_shape' => $source->answer_shape,
            'requires_grounding' => $source->requires_grounding,
            'grounding_available' => $source->grounding_available,
            'metadata' => $this->replayMetadata($source),
        ]);

        ProviderResponseCatalog::latestForVerification($source->id)
            ->each(fn (ProviderResponse $response): ProviderResponse => $this->copyProviderResponse($response, $replay));

        return $this->workflow->replayFromPersisted($replay);
    }

    public function replayFixture(string $fixtureId): VerificationRequest
    {
        $source = VerificationRequest::query()
            ->where('metadata->fixture_id', $fixtureId)
            ->latest('id')
            ->firstOrFail();

        return $this->replayRequest($source->id);
    }

    /**
     * @return array<string, mixed>
     */
    public function auditTrailForRequest(int $verificationRequestId): array
    {
        $request = $this->sourceRequest($verificationRequestId);

        return [
            'request' => [
                'id' => $request->id,
                'question' => $request->question,
                'created_at' => $request->created_at?->toISOString(),
                'updated_at' => $request->updated_at?->toISOString(),
                'metadata' => $request->metadata,
            ],
            'classification' => [
                'classified_type' => $request->classified_type,
                'classifier_confidence' => $request->classifier_confidence,
                'answer_shape' => $request->answer_shape,
                'requires_grounding' => $request->requires_grounding,
                'grounding_available' => $request->grounding_available,
            ],
            'providers' => ProviderResponseCatalog::latestForVerification($request->id)
                ->map(fn (ProviderResponse $response): array => $this->providerAuditPayload($response))
                ->values()
                ->all(),
            'consensus_result' => $this->consensusAuditPayload($request),
        ];
    }

    private function sourceRequest(int $verificationRequestId): VerificationRequest
    {
        return VerificationRequest::with([
            'consensusResult',
        ])->findOrFail($verificationRequestId);
    }

    /**
     * @return array<string, mixed>
     */
    private function replayMetadata(VerificationRequest $source): array
    {
        $metadata = $source->metadata ?? [];

        $metadata['replay'] = [
            'source_request_id' => $source->id,
            'source_fixture_id' => $metadata['fixture_id'] ?? null,
            'replayed_at' => now()->toISOString(),
        ];

        return $metadata;
    }

    private function copyProviderResponse(ProviderResponse $source, VerificationRequest $replay): ProviderResponse
    {
        return ProviderResponse::create([
            'verification_request_id' => $replay->id,
            'provider' => $source->provider,
            'model' => $source->model,
            'provider_prompt' => $source->provider_prompt,
            'provider_status' => $source->provider_status,
            'extraction_prompt' => $source->extraction_prompt,
            'extractor_model' => $source->extractor_model,
            'extraction_status' => $source->extraction_status,
            'raw_answer' => $source->raw_answer,
            'normalized' => $source->normalized,
            'usage' => $source->usage,
            'error' => $source->error,
            'metadata' => $source->metadata,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function providerAuditPayload(ProviderResponse $response): array
    {
        return [
            'provider' => $response->provider,
            'model' => $response->model,
            'provider_prompt' => $response->provider_prompt,
            'provider_status' => $response->provider_status,
            'raw_answer' => $response->raw_answer,
            'extraction_prompt' => $response->extraction_prompt,
            'extractor_model' => $response->extractor_model,
            'extraction_status' => $response->extraction_status,
            'normalized' => $response->normalized,
            'claims' => $response->normalized['claims'] ?? null,
            'usage' => $response->usage,
            'error' => $response->error,
            'metadata' => $response->metadata,
            'created_at' => $response->created_at?->toISOString(),
            'updated_at' => $response->updated_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private function consensusAuditPayload(VerificationRequest $request): ?array
    {
        $result = $request->consensusResult;

        if ($result === null) {
            return null;
        }

        return [
            'alignment' => $result->alignment,
            'conflict_detection' => $result->conflict_detection,
            'consensus' => $result->consensus,
            'decision_key' => $result->decision_key,
            'decision_basis' => $result->decision_basis,
            'trust_base' => $result->trust_base,
            'applied_caps' => $result->applied_caps,
            'trust_level' => $result->trust_level,
            'verdict_report' => $result->verdict_report,
            'errors' => $result->errors,
            'metadata' => $result->metadata,
            'created_at' => $result->created_at?->toISOString(),
            'updated_at' => $result->updated_at?->toISOString(),
        ];
    }
}
