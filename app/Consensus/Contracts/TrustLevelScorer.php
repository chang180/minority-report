<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\TrustLevelResult;

interface TrustLevelScorer
{
    public function score(
        ClassificationResult $classification,
        ConsensusResult $consensus,
        AnalysisContext $context,
    ): TrustLevelResult;
}
