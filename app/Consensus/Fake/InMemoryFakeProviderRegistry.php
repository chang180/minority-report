<?php

namespace App\Consensus\Fake;

use App\Consensus\Contracts\FakeProviderRegistry;
use App\Consensus\Contracts\LlmProvider;

class InMemoryFakeProviderRegistry implements FakeProviderRegistry
{
    /** @var array<string, \Closure> */
    private array $behaviors = [];

    public function register(string $fixtureId, callable $behavior): void
    {
        $this->behaviors[$fixtureId] = $behavior instanceof \Closure
            ? $behavior
            : \Closure::fromCallable($behavior);
    }

    public function create(string $fixtureId): LlmProvider
    {
        if (! isset($this->behaviors[$fixtureId])) {
            throw new \InvalidArgumentException("No behavior registered for fixture '{$fixtureId}'");
        }

        return new FakeLlmProvider($fixtureId, $this->behaviors[$fixtureId]);
    }
}
