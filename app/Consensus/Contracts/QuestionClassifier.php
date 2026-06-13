<?php

namespace App\Consensus\Contracts;

use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;

interface QuestionClassifier
{
    /**
     * @throws ClassifierException
     */
    public function classify(Question $question): ClassificationResult;
}
