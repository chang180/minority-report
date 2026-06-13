<?php

namespace App\Consensus\Classifier;

use App\Consensus\Contracts\QuestionClassifier;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;

class FailSafeQuestionClassifier implements QuestionClassifier
{
    public function classify(Question $question): ClassificationResult
    {
        $classification = $this->classificationFromMetadata($question)
            ?? $this->classifyWithRules($question);

        return $this->applyFailSafeBias($classification);
    }

    public function applyFailSafeBias(ClassificationResult $classification): ClassificationResult
    {
        if ($classification->classifierConfidence === 'high') {
            return $classification;
        }

        if ($classification->type === 'A') {
            return new ClassificationResult(
                type: 'B',
                answerShape: $classification->answerShape,
                requiresGrounding: false,
                classifierConfidence: $classification->classifierConfidence,
            );
        }

        return new ClassificationResult(
            type: 'C',
            answerShape: $classification->answerShape,
            requiresGrounding: true,
            classifierConfidence: $classification->classifierConfidence,
        );
    }

    private function classificationFromMetadata(Question $question): ?ClassificationResult
    {
        $classification = $question->metadata['classification'] ?? null;

        if (! is_array($classification)) {
            return null;
        }

        return $this->sanitize(
            type: $classification['type'] ?? 'B',
            answerShape: $classification['answer_shape'] ?? $classification['answerShape'] ?? 'open',
            requiresGrounding: (bool) ($classification['requires_grounding'] ?? $classification['requiresGrounding'] ?? false),
            classifierConfidence: $classification['classifier_confidence'] ?? $classification['classifierConfidence'] ?? 'low',
        );
    }

    private function classifyWithRules(Question $question): ClassificationResult
    {
        $text = mb_strtolower($question->text);
        $answerShape = $this->looksDiscrete($text) ? 'discrete' : 'open';

        if ($this->requiresCurrentInformation($text)) {
            return new ClassificationResult(
                type: 'C',
                answerShape: $answerShape,
                requiresGrounding: true,
                classifierConfidence: 'high',
            );
        }

        if ($this->looksSubjectiveOrCreative($text)) {
            return new ClassificationResult(
                type: 'A',
                answerShape: 'open',
                requiresGrounding: false,
                classifierConfidence: 'high',
            );
        }

        return new ClassificationResult(
            type: 'B',
            answerShape: $answerShape,
            requiresGrounding: false,
            classifierConfidence: 'high',
        );
    }

    private function sanitize(
        mixed $type,
        mixed $answerShape,
        bool $requiresGrounding,
        mixed $classifierConfidence,
    ): ClassificationResult {
        $safeType = in_array($type, ['A', 'B', 'C'], true) ? $type : 'C';
        $safeAnswerShape = in_array($answerShape, ['discrete', 'open'], true) ? $answerShape : 'open';
        $safeConfidence = in_array($classifierConfidence, ['high', 'low'], true) ? $classifierConfidence : 'low';

        return new ClassificationResult(
            type: $safeType,
            answerShape: $safeAnswerShape,
            requiresGrounding: $safeType === 'C' ? true : $requiresGrounding,
            classifierConfidence: $safeConfidence,
        );
    }

    private function requiresCurrentInformation(string $text): bool
    {
        return preg_match('/\b(latest|current|today|now|news|price|stock|law|legal|version|release|recent|this month|this year)\b/u', $text) === 1;
    }

    private function looksSubjectiveOrCreative(string $text): bool
    {
        return preg_match('/\b(write|draft|compose|brainstorm|opinion|prefer|should i|story|poem|tagline|slogan)\b/u', $text) === 1;
    }

    private function looksDiscrete(string $text): bool
    {
        return preg_match('/\b(is|are|was|were|do|does|did|can|will|has|have|when|who|which|what version|how many|how much)\b/u', $text) === 1;
    }
}
