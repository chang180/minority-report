<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\DTO\VerdictInput;
use App\Consensus\DTO\VerdictReport;

class NullVerdictReporter implements VerdictReporter
{
    public function report(VerdictInput $input): VerdictReport
    {
        throw new \RuntimeException('Not implemented until M4');
    }
}
