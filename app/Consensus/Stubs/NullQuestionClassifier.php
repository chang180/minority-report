<?php

namespace App\Consensus\Stubs;

use App\Consensus\Contracts\QuestionClassifier;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;

class NullQuestionClassifier implements QuestionClassifier
{
    public function classify(Question $question): ClassificationResult
    {
        throw new \RuntimeException('Not implemented until M4');
    }
}
