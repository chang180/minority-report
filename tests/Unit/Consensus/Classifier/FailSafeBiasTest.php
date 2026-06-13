<?php

use App\Consensus\Classifier\FailSafeQuestionClassifier;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;

test('CT-G1 low confidence type B is upgraded to type C with grounding', function () {
    $classifier = new FailSafeQuestionClassifier;

    $result = $classifier->applyFailSafeBias(new ClassificationResult(
        type: 'B',
        answerShape: 'discrete',
        requiresGrounding: false,
        classifierConfidence: 'low',
    ));

    expect($result->type)->toBe('C')
        ->and($result->requiresGrounding)->toBeTrue()
        ->and($result->answerShape)->toBe('discrete')
        ->and($result->classifierConfidence)->toBe('low');
});

test('CT-G2 low confidence type A is upgraded to type B', function () {
    $classifier = new FailSafeQuestionClassifier;

    $result = $classifier->applyFailSafeBias(new ClassificationResult(
        type: 'A',
        answerShape: 'open',
        requiresGrounding: false,
        classifierConfidence: 'low',
    ));

    expect($result->type)->toBe('B')
        ->and($result->requiresGrounding)->toBeFalse()
        ->and($result->answerShape)->toBe('open')
        ->and($result->classifierConfidence)->toBe('low');
});

test('CT-G3 high confidence output is not changed', function () {
    $classifier = new FailSafeQuestionClassifier;

    $result = $classifier->applyFailSafeBias(new ClassificationResult(
        type: 'B',
        answerShape: 'open',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($result->type)->toBe('B')
        ->and($result->requiresGrounding)->toBeFalse()
        ->and($result->answerShape)->toBe('open')
        ->and($result->classifierConfidence)->toBe('high');
});

test('classification metadata uses the same fail safe post processing path', function () {
    $classifier = new FailSafeQuestionClassifier;

    $result = $classifier->classify(new Question('What is the latest Laravel version?', [
        'classification' => [
            'type' => 'B',
            'answer_shape' => 'discrete',
            'requires_grounding' => false,
            'classifier_confidence' => 'low',
        ],
    ]));

    expect($result->type)->toBe('C')
        ->and($result->requiresGrounding)->toBeTrue();
});
