<?php

use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\Extractor\JsonResponseExtractor;

test('extracts normalized json from a single provider raw answer', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'openai',
        model: 'gpt-test',
        providerStatus: 'success',
        extractionStatus: 'not_started',
        rawAnswer: json_encode([
            'answer_shape' => 'discrete',
            'direct_answer' => 'yes',
            'summary' => 'Laravel migrations manage database schema changes.',
            'claims' => [
                [
                    'type' => 'boolean',
                    'canonical_key' => 'Laravel migration purpose',
                    'subject' => 'Laravel migrations',
                    'predicate' => 'manage',
                    'value' => 'database schema changes',
                ],
            ],
            'citations' => ['https://example.test/docs'],
        ], JSON_THROW_ON_ERROR),
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'discrete',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->extractionStatus)->toBe('success')
        ->and($extracted->normalized['answer_shape'])->toBe('discrete')
        ->and($extracted->normalized['direct_answer'])->toBe('yes')
        ->and($extracted->normalized['claims'][0]['canonical_key'])->toBe('laravel migration purpose')
        ->and($extracted->extractionPrompt)->toContain('Provider: openai')
        ->and($extracted->extractorModel)->toBe('fixture-json-replay');
});

test('open questions always use not applicable direct answer', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'anthropic',
        providerStatus: 'success',
        rawAnswer: json_encode([
            'direct_answer' => 'yes',
            'summary' => 'MVC separates application concerns.',
            'claims' => [],
            'citations' => [],
        ], JSON_THROW_ON_ERROR),
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'open',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->extractionStatus)->toBe('success')
        ->and($extracted->normalized['answer_shape'])->toBe('open')
        ->and($extracted->normalized['direct_answer'])->toBe('not_applicable');
});

test('invalid json is marked without reprompting', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'gemini',
        providerStatus: 'success',
        rawAnswer: '{"summary": "missing close"',
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'discrete',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->providerStatus)->toBe('success')
        ->and($extracted->extractionStatus)->toBe('invalid_json')
        ->and($extracted->normalized)->toBeNull()
        ->and($extracted->error['message'])->toBe('Extractor JSON could not be decoded.');
});

test('boolean direct answer is coerced to yes or no', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'openai',
        providerStatus: 'success',
        rawAnswer: '{"direct_answer": true, "summary": "Water is densest at 4C.", "claims": [], "citations": []}',
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'discrete',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->extractionStatus)->toBe('success')
        ->and($extracted->normalized['direct_answer'])->toBe('yes');
});

test('markdown json and chinese direct answers are normalized', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'openai',
        providerStatus: 'success',
        rawAnswer: "```json\n{\"direct_answer\":\"是\",\"summary\":\"1加1等於2。\",\"claims\":[],\"citations\":[]}\n```",
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'discrete',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->extractionStatus)->toBe('success')
        ->and($extracted->normalized['direct_answer'])->toBe('yes');
});

test('discrete answer can be inferred from summary when direct answer is missing', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'anthropic',
        providerStatus: 'success',
        rawAnswer: '{"summary":"yes","claims":[],"citations":[]}',
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'discrete',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->extractionStatus)->toBe('success')
        ->and($extracted->normalized['direct_answer'])->toBe('yes');
});

test('provider failures keep extraction not started', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'openai',
        providerStatus: 'failed_timeout',
        extractionStatus: 'not_started',
        rawAnswer: '',
    );

    $extracted = $extractor->extract($response, new ClassificationResult);

    expect($extracted->providerStatus)->toBe('failed_timeout')
        ->and($extracted->extractionStatus)->toBe('not_started')
        ->and($extracted->normalized)->toBeNull();
});

test('string claims from local llms are coerced into numeric claims', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'gemini',
        providerStatus: 'success',
        rawAnswer: <<<'JSON'
```json
{
  "direct_answer": "not_applicable",
  "summary": "水銀的標準熔點約為攝氏零下38.83度。",
  "claims": [
    "水銀的標準熔點約為攝氏零下38.83度。"
  ],
  "citations": []
}
```
JSON,
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'open',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->extractionStatus)->toBe('success')
        ->and($extracted->normalized['claims'])->toHaveCount(1)
        ->and($extracted->normalized['claims'][0]['type'])->toBe('number')
        ->and($extracted->normalized['claims'][0]['value'])->toBe('-38.83')
        ->and($extracted->normalized['claims'][0]['unit'])->toBe('°C');
});

test('numeric summary is promoted to a number claim when claims are empty', function () {
    $extractor = new JsonResponseExtractor;
    $response = new ProviderResponse(
        provider: 'openai',
        providerStatus: 'success',
        rawAnswer: '{"direct_answer":"not_applicable","summary":"水銀的標準熔點約為攝氏零下38.83度。","claims":[],"citations":[]}',
    );

    $extracted = $extractor->extract($response, new ClassificationResult(
        type: 'B',
        answerShape: 'open',
        requiresGrounding: false,
        classifierConfidence: 'high',
    ));

    expect($extracted->normalized['claims'][0]['type'])->toBe('number')
        ->and($extracted->normalized['claims'][0]['value'])->toBe('-38.83');
});
