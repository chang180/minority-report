<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\ProviderResponse;

interface ConsensusAnalyzer
{
    /**
     * @param  ProviderResponse[]  $analyzableResponses
     */
    public function analyze(
        ClassificationResult $classification,
        array $analyzableResponses,
        AlignmentResult $alignment,
    ): ConsensusResult;
}
