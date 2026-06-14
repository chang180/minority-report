<?php

namespace App\Consensus;

use App\Consensus\Contracts\ClaimAligner;
use App\Consensus\Contracts\ConsensusAnalyzer;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\Contracts\QuestionClassifier;
use App\Consensus\Contracts\TrustLevelScorer;
use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult as ConsensusResultDto;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\Question;
use App\Consensus\DTO\TrustLevelResult;
use App\Consensus\DTO\VerdictInput;
use App\Consensus\DTO\VerdictReport;
use App\Models\ConsensusResult as ConsensusResultModel;
use App\Models\ProviderResponse as ProviderResponseModel;
use App\Models\VerificationRequest;

class ConsensusWorkflow
{
    public function __construct(
        private readonly QuestionClassifier $classifier,
        private readonly ProviderOrchestrator $providerOrchestrator,
        private readonly ResponseExtractionOrchestrator $extractionOrchestrator,
        private readonly ClaimAligner $aligner,
        private readonly ConsensusAnalyzer $analyzer,
        private readonly TrustLevelScorer $trustLevelScorer,
        private readonly VerdictReporter $verdictReporter,
    ) {}

    /**
     * @param  LlmProvider[]  $providers
     */
    public function run(Question $question, array $providers, ?string $providerPrompt = null, ?VerificationRequest $existingRequest = null): VerificationRequest
    {
        $classification = $this->classifier->classify($question);
        $groundingAvailable = (bool) ($question->metadata['grounding_available'] ?? false);
        $groundingStatus = (string) ($question->metadata['grounding']['status'] ?? '');

        if ($existingRequest !== null) {
            $existingRequest->update([
                'classified_type' => $classification->type,
                'classifier_confidence' => $classification->classifierConfidence,
                'answer_shape' => $classification->answerShape,
                'requires_grounding' => $classification->requiresGrounding,
                'grounding_available' => $groundingAvailable,
                'metadata' => $question->metadata,
            ]);
            $verificationRequest = $existingRequest;
        } else {
            $verificationRequest = VerificationRequest::create([
                'question' => $question->text,
                'classified_type' => $classification->type,
                'classifier_confidence' => $classification->classifierConfidence,
                'answer_shape' => $classification->answerShape,
                'requires_grounding' => $classification->requiresGrounding,
                'grounding_available' => $groundingAvailable,
                'metadata' => $question->metadata,
            ]);
        }

        $prompt = $providerPrompt ?? $this->buildProviderPrompt($question, $classification);
        $rawResponses = $this->providerOrchestrator->dispatch($verificationRequest->id, $question, $prompt, $providers);
        $providerResponses = $this->extractionOrchestrator->extractAndPersist(
            $verificationRequest->id,
            $rawResponses,
            $classification,
        );

        return $this->completeFromResponses($verificationRequest, $classification, $providerResponses, $groundingAvailable, $groundingStatus);
    }

    public function replayFromPersisted(VerificationRequest $verificationRequest): VerificationRequest
    {
        $verificationRequest->loadMissing(['providerResponses' => fn ($query) => $query->oldest('id')]);

        return $this->completeFromResponses(
            verificationRequest: $verificationRequest,
            classification: $this->classificationFromRequest($verificationRequest),
            providerResponses: $verificationRequest->providerResponses
                ->map(fn (ProviderResponseModel $response): ProviderResponse => $this->providerResponseFromModel($response))
                ->all(),
            groundingAvailable: $verificationRequest->grounding_available,
            groundingStatus: (string) ($verificationRequest->metadata['grounding']['status'] ?? ''),
        );
    }

    /**
     * @param  ProviderResponse[]  $providerResponses
     */
    private function completeFromResponses(
        VerificationRequest $verificationRequest,
        ClassificationResult $classification,
        array $providerResponses,
        bool $groundingAvailable,
        string $groundingStatus = '',
    ): VerificationRequest {
        $analyzableResponses = $this->analyzableResponses($providerResponses);
        $alignment = $this->aligner->align($analyzableResponses);
        $consensus = $this->analyzer->analyze($classification, $analyzableResponses, $alignment);
        $context = $this->analysisContext($classification, $providerResponses, $analyzableResponses, $groundingAvailable, $groundingStatus);
        $trustLevel = $this->trustLevelScorer->score($classification, $consensus, $context);
        $verdictReport = $this->verdictReporter->report(new VerdictInput(
            classification: $classification,
            providerResponses: $providerResponses,
            alignment: $alignment,
            consensus: $consensus,
            trustLevel: $trustLevel,
            context: $context,
        ));

        $this->persistConsensusResult(
            verificationRequest: $verificationRequest,
            classification: $classification,
            providerResponses: $providerResponses,
            alignment: $alignment,
            consensus: $consensus,
            context: $context,
            trustLevel: $trustLevel,
            verdictReport: $verdictReport,
        );

        return $verificationRequest->refresh()->load(['providerResponses', 'consensusResult']);
    }

    private function classificationFromRequest(VerificationRequest $verificationRequest): ClassificationResult
    {
        return new ClassificationResult(
            type: $verificationRequest->classified_type ?? 'B',
            answerShape: $verificationRequest->answer_shape ?? 'open',
            requiresGrounding: $verificationRequest->requires_grounding,
            classifierConfidence: $verificationRequest->classifier_confidence ?? 'low',
        );
    }

    private function providerResponseFromModel(ProviderResponseModel $response): ProviderResponse
    {
        return new ProviderResponse(
            provider: $response->provider,
            model: $response->model ?? '',
            providerStatus: $response->provider_status,
            extractionStatus: $response->extraction_status,
            rawAnswer: $response->raw_answer ?? '',
            normalized: $response->normalized,
            usage: $response->usage ?? [],
            error: $response->error,
            metadata: $response->metadata ?? [],
            extractionPrompt: $response->extraction_prompt ?? '',
            extractorModel: $response->extractor_model ?? '',
        );
    }

    private function buildProviderPrompt(Question $question, ClassificationResult $classification): string
    {
        return implode("\n", [
            'Answer the user question for consensus verification.',
            'Question: '.$question->text,
            'Expected answer shape: '.$classification->answerShape,
        ]);
    }

    /**
     * @param  ProviderResponse[]  $providerResponses
     * @return ProviderResponse[]
     */
    private function analyzableResponses(array $providerResponses): array
    {
        return array_values(array_filter(
            $providerResponses,
            fn (ProviderResponse $response): bool => $response->providerStatus === 'success'
                && $response->extractionStatus === 'success',
        ));
    }

    /**
     * @param  ProviderResponse[]  $providerResponses
     * @param  ProviderResponse[]  $analyzableResponses
     */
    private function analysisContext(
        ClassificationResult $classification,
        array $providerResponses,
        array $analyzableResponses,
        bool $groundingAvailable,
        string $groundingStatus = '',
    ): AnalysisContext {
        return new AnalysisContext(
            groundingAvailable: $groundingAvailable,
            providerCount: count($providerResponses),
            analyzableCount: count($analyzableResponses),
            effectiveVoteCount: $classification->answerShape === 'discrete'
                ? $this->effectiveVoteCount($analyzableResponses)
                : -1,
            metadata: [
                'grounding_status' => $groundingStatus,
                'provider_statuses' => array_map(
                    fn (ProviderResponse $response): array => [
                        'provider' => $response->provider,
                        'provider_status' => $response->providerStatus,
                        'extraction_status' => $response->extractionStatus,
                    ],
                    $providerResponses,
                ),
            ],
        );
    }

    /**
     * @param  ProviderResponse[]  $analyzableResponses
     */
    private function effectiveVoteCount(array $analyzableResponses): int
    {
        return count(array_filter(
            $analyzableResponses,
            fn (ProviderResponse $response): bool => ($response->normalized['direct_answer'] ?? 'unknown') !== 'unknown',
        ));
    }

    /**
     * @param  ProviderResponse[]  $providerResponses
     */
    private function persistConsensusResult(
        VerificationRequest $verificationRequest,
        ClassificationResult $classification,
        array $providerResponses,
        AlignmentResult $alignment,
        ConsensusResultDto $consensus,
        AnalysisContext $context,
        TrustLevelResult $trustLevel,
        VerdictReport $verdictReport,
    ): void {
        ConsensusResultModel::updateOrCreate(
            ['verification_request_id' => $verificationRequest->id],
            [
                'alignment' => $this->alignmentPayload($alignment),
                'conflict_detection' => $consensus->conflicts,
                'consensus' => $this->consensusPayload($consensus),
                'decision_key' => $classification->answerShape === 'discrete' ? 'direct_answer' : 'claims',
                'decision_basis' => $this->decisionBasisPayload($classification, $consensus, $context),
                'trust_base' => $trustLevel->base,
                'applied_caps' => $trustLevel->caps,
                'trust_level' => $trustLevel->trustLevel,
                'verdict_report' => $this->verdictPayload($verdictReport),
                'errors' => $this->errorPayload($providerResponses),
                'metadata' => [
                    'classification' => $this->classificationPayload($classification),
                    'context' => $this->contextPayload($context),
                ],
            ],
        );

        $verificationRequest->update([
            'consensus_summary' => $this->consensusPayload($consensus),
            'final_trust' => $trustLevel->trustLevel,
            'final_verdict' => $verdictReport->verdict !== '' ? $verdictReport->verdict : null,
            'errors' => $this->errorPayload($providerResponses),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function alignmentPayload(AlignmentResult $alignment): array
    {
        return [
            'aligned' => $alignment->aligned,
            'unmatched' => $alignment->unmatched,
            'unalignable' => $alignment->unalignable,
            'metadata' => $alignment->metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function consensusPayload(ConsensusResultDto $consensus): array
    {
        return [
            'status' => $consensus->status,
            'majority_provider' => $consensus->majorityProvider,
            'minority_provider' => $consensus->minorityProvider,
            'metadata' => $consensus->metadata,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function decisionBasisPayload(
        ClassificationResult $classification,
        ConsensusResultDto $consensus,
        AnalysisContext $context,
    ): array {
        return [
            'answer_shape' => $classification->answerShape,
            'consensus_status' => $consensus->status,
            'analyzable_provider_count' => $context->analyzableCount,
            'effective_direct_answer_vote_count' => $context->effectiveVoteCount,
            'has_major_claim_conflict' => count($consensus->conflicts) > 0,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function verdictPayload(VerdictReport $verdictReport): array
    {
        return [
            'verdict' => $verdictReport->verdict,
            'summary' => $verdictReport->summary,
            'metadata' => $verdictReport->metadata,
        ];
    }

    /**
     * @param  ProviderResponse[]  $providerResponses
     * @return array<int, array<string, mixed>>
     */
    private function errorPayload(array $providerResponses): array
    {
        return array_values(array_filter(array_map(
            fn (ProviderResponse $response): ?array => $response->error === null
                ? null
                : [
                    'provider' => $response->provider,
                    'provider_status' => $response->providerStatus,
                    'extraction_status' => $response->extractionStatus,
                    'error' => $response->error,
                ],
            $providerResponses,
        )));
    }

    /**
     * @return array<string, mixed>
     */
    private function classificationPayload(ClassificationResult $classification): array
    {
        return [
            'type' => $classification->type,
            'answer_shape' => $classification->answerShape,
            'requires_grounding' => $classification->requiresGrounding,
            'classifier_confidence' => $classification->classifierConfidence,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function contextPayload(AnalysisContext $context): array
    {
        return [
            'grounding_available' => $context->groundingAvailable,
            'provider_count' => $context->providerCount,
            'analyzable_provider_count' => $context->analyzableCount,
            'effective_direct_answer_vote_count' => $context->effectiveVoteCount,
            'metadata' => $context->metadata,
        ];
    }
}
