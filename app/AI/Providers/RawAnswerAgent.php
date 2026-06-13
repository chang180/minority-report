<?php

namespace App\AI\Providers;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class RawAnswerAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return 'Answer the user question directly. Preserve uncertainty and avoid fabricating sources.';
    }
}
