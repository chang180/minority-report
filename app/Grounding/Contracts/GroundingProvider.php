<?php

namespace App\Grounding\Contracts;

use App\Grounding\DTO\GroundingResult;

interface GroundingProvider
{
    public function fetch(string $questionText): GroundingResult;
}
