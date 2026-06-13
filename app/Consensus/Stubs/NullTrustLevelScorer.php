<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\TrustLevelScorer;
use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\TrustLevelResult;

class NullTrustLevelScorer implements TrustLevelScorer
{
    public function score(
        ClassificationResult $classification,
        ConsensusResult $consensus,
        AnalysisContext $context,
    ): TrustLevelResult {
        throw new \RuntimeException('Not implemented until M4');
    }
}
