<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ProviderResponse;

interface ClaimAligner
{
    /**
     * @param  ProviderResponse[]  $analyzableResponses
     */
    public function align(array $analyzableResponses): AlignmentResult;
}
