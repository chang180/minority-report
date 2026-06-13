<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\VerdictInput;
use App\Consensus\DTO\VerdictReport;

interface VerdictReporter
{
    /**
     * LLM-assisted narrative only; MUST NOT override deterministic consensus.
     */
    public function report(VerdictInput $input): VerdictReport;
}
