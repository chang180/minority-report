<?php

use App\AI\Providers\ConfiguredRawAnswerAgent;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\Question;
use App\Consensus\Prompt\ProviderPromptBuilder;
use Illuminate\JsonSchema\JsonSchemaTypeFactory;

test('configured agent defines consensus structured output schema', function () {
    $agent = new ConfiguredRawAnswerAgent;
    $schema = $agent->schema(new JsonSchemaTypeFactory);

    expect($schema)->toHaveKeys(['direct_answer', 'summary', 'claims', 'citations']);
});

test('configured agent instructions require Traditional Chinese summaries', function () {
    $instructions = (new ConfiguredRawAnswerAgent)->instructions();

    expect($instructions)->toContain('Traditional Chinese')
        ->and($instructions)->toContain('繁體中文');
});

test('provider prompt builder includes question and answer shape guidance in Traditional Chinese', function () {
    $builder = new ProviderPromptBuilder;

    $discrete = $builder->build(
        new Question('Is water densest at 4C?'),
        new ClassificationResult(type: 'B', answerShape: 'discrete', requiresGrounding: false, classifierConfidence: 'high'),
    );

    expect($discrete)->toContain('Is water densest at 4C?')
        ->and($discrete)->toContain('預期答案型態：discrete')
        ->and($discrete)->toContain('繁體中文')
        ->and($discrete)->toContain('yes、no、unknown');

    $open = $builder->build(
        new Question('Explain MVC.'),
        new ClassificationResult(type: 'B', answerShape: 'open', requiresGrounding: false, classifierConfidence: 'high'),
        "External grounding summary (non-authoritative, for reference):\nExample summary",
    );

    expect($open)->toContain('not_applicable')
        ->and($open)->toContain('Example summary');
});

test('provider prompt builder appends grounding context without replacing question', function () {
    $builder = new ProviderPromptBuilder;
    $prompt = $builder->build(
        new Question('Test question here?'),
        new ClassificationResult(type: 'B', answerShape: 'discrete', requiresGrounding: true, classifierConfidence: 'high'),
        "External grounding summary (non-authoritative, for reference):\nGrounding text",
    );

    expect($prompt)->toContain('Test question here?')
        ->and($prompt)->toContain('Grounding text');
});
