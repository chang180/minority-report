<?php

namespace App\Consensus\Contracts;

interface FakeProviderRegistry
{
    public function register(string $fixtureId, callable $behavior): void;

    public function create(string $fixtureId): LlmProvider;
}
