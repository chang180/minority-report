<?php

use App\Consensus\Demo\ConsensusDemoFixtureCatalog;

test('demo fixture catalog exposes Traditional Chinese labels and sample questions', function () {
    $options = app(ConsensusDemoFixtureCatalog::class)->options();

    $minority = collect($options)->firstWhere('id', 'M6-F02');

    expect($minority)->not->toBeNull()
        ->and($minority['label'])->toBe('少數意見報告')
        ->and($minority['description'])->toContain('Gemini')
        ->and($minority['sample_question'])->toBe('產品發布日期是否通過共識驗證？')
        ->and($minority['expected_consensus'])->toBe('Majority');
});

test('demo fixture providers return Traditional Chinese summaries', function () {
    $providers = app(ConsensusDemoFixtureCatalog::class)->providersFor('M6-F02');
    $openai = collect($providers)->first(fn ($p) => $p->name() === 'openai');

    $response = $openai->ask(
        new \App\Consensus\DTO\Question('產品發布日期是否通過共識驗證？'),
        '',
    );

    $decoded = json_decode($response->rawAnswer ?? '', true);

    expect($decoded['summary'])->toBe('OpenAI 席認為該說法成立。');
});
