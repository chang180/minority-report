<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\ClaimAligner;
use App\Consensus\DTO\AlignmentResult;

class NullClaimAligner implements ClaimAligner
{
    public function align(array $analyzableResponses): AlignmentResult
    {
        throw new \RuntimeException('Not implemented until M4');
    }
}
