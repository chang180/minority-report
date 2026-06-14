<?php

namespace App\Consensus;

use Illuminate\Support\Facades\Concurrency;
use Throwable;

class ConsensusParallelRunner
{
    /**
     * Run independent tasks concurrently when enabled. Falls back to sequential execution
     * when parallel dispatch is disabled or the concurrency driver fails.
     *
     * @param  array<int|string, callable(): mixed>  $tasks
     * @return array<int|string, mixed>
     */
    public function run(array $tasks): array
    {
        if ($tasks === []) {
            return [];
        }

        if (count($tasks) === 1 || ! $this->enabled()) {
            return $this->runSequential($tasks);
        }

        try {
            /** @var array<int|string, mixed> $results */
            $results = Concurrency::run($tasks);

            return $results;
        } catch (Throwable) {
            return $this->runSequential($tasks);
        }
    }

    /**
     * @param  array<int|string, callable(): mixed>  $tasks
     * @return array<int|string, mixed>
     */
    private function runSequential(array $tasks): array
    {
        $results = [];

        foreach ($tasks as $key => $task) {
            $results[$key] = $task();
        }

        return $results;
    }

    private function enabled(): bool
    {
        return (bool) config('consensus.parallel.enabled', true);
    }
}
