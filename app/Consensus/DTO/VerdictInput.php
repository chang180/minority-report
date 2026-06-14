<?php

namespace App\Consensus\DTO;

use App\Consensus\Synthesis\SynthesisRequest;

final readonly class VerdictInput
{
    /**
     * @param  ProviderResponse[]  $providerResponses
     */
    public function __construct(
        public ClassificationResult $classification,
        public array $providerResponses,
        public AlignmentResult $alignment,
        public ConsensusResult $consensus,
        public TrustLevelResult $trustLevel,
        public AnalysisContext $context,
        public string $questionText = '',
        public ?SynthesisRequest $synthesis = null,
    ) {}
}
