<?php

namespace App\Grounding\Providers;

use App\Grounding\Contracts\GroundingProvider;
use App\Grounding\DTO\GroundingResult;

class DisabledGroundingProvider implements GroundingProvider
{
    public function fetch(string $questionText): GroundingResult
    {
        return GroundingResult::skipped($questionText);
    }
}
