<?php

namespace App\Consensus\Prompt;

use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;

class ProviderPromptBuilder
{
    public function build(Question $question, ClassificationResult $classification, ?string $groundingContext = null): string
    {
        $lines = [
            'Answer the user question for consensus verification.',
            'Question: '.$question->text,
            'Expected answer shape: '.$classification->answerShape,
        ];

        if ($classification->answerShape === 'discrete') {
            $lines[] = 'Set direct_answer to exactly one of: yes, no, unknown.';
            $lines[] = 'Example JSON shape: {"direct_answer":"yes","summary":"One sentence.","claims":[],"citations":[]}';
        } else {
            $lines[] = 'Set direct_answer to not_applicable for this open-ended question.';
        }

        if ($groundingContext !== null && trim($groundingContext) !== '') {
            $lines[] = '';
            $lines[] = trim($groundingContext);
        }

        return implode("\n", $lines);
    }
}
