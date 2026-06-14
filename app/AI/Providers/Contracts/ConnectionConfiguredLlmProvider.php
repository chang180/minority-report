<?php

namespace App\AI\Providers\Contracts;

use App\AI\Providers\LlmConnectionConfig;
use App\Consensus\Contracts\LlmProvider;

interface ConnectionConfiguredLlmProvider extends LlmProvider
{
    public function connectionConfig(): LlmConnectionConfig;
}
