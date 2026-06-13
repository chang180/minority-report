<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\ConsensusAnalyzer;
use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;

class NullConsensusAnalyzer implements ConsensusAnalyzer
{
    public function analyze(
        ClassificationResult $classification,
        array $analyzableResponses,
        AlignmentResult $alignment,
    ): ConsensusResult {
        throw new \RuntimeException('Not implemented until M4');
    }
}
