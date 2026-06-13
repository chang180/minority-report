<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\FakeProviderRegistry;
use App\Consensus\Contracts\LlmProvider;

class NullFakeProviderRegistry implements FakeProviderRegistry
{
    public function register(string $fixtureId, callable $behavior): void
    {
        throw new \RuntimeException('Not implemented until M3');
    }

    public function create(string $fixtureId): LlmProvider
    {
        throw new \RuntimeException('Not implemented until M3');
    }
}
