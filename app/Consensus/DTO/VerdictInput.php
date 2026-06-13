<?php

namespace App\Consensus\DTO;

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
    ) {}
}
