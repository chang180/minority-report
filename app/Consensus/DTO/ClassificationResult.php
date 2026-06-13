<?php

namespace App\Consensus\DTO;

final readonly class ClassificationResult
{
    /**
     * @param  'A'|'B'|'C'  $type
     * @param  'discrete'|'open'  $answerShape
     * @param  'high'|'low'  $classifierConfidence
     */
    public function __construct(
        public string $type = 'B',
        public string $answerShape = 'open',
        public bool $requiresGrounding = false,
        public string $classifierConfidence = 'low',
    ) {}
}
