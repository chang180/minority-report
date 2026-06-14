<?php

use App\AI\Providers\ProviderGenerationOptions;

test('parseJson accepts object with known keys', function () {
    $options = ProviderGenerationOptions::parseJson('{"max_tokens": 4096, "temperature": 0.5}');

    expect($options)->toMatchArray([
        'max_tokens' => 4096,
        'temperature' => 0.5,
    ]);
});

test('parseJson clamps max_tokens to allowed range', function () {
    $options = ProviderGenerationOptions::parseJson('{"max_tokens": 999999}');

    expect($options['max_tokens'])->toBe(32768);
});

test('parseJson rejects invalid json', function () {
    ProviderGenerationOptions::parseJson('{not json}');
})->throws(\InvalidArgumentException::class, '額外參數必須是有效的 JSON 物件。');

test('parseJson rejects json array', function () {
    ProviderGenerationOptions::parseJson('[1, 2, 3]');
})->throws(\InvalidArgumentException::class, '額外參數必須是 JSON 物件（例如 {"max_tokens": 2048}）。');

test('parseJson allows additional scalar keys for forward compatibility', function () {
    $options = ProviderGenerationOptions::parseJson('{"max_tokens": 512, "seed": 42, "stop": "END"}');

    expect($options)->toMatchArray([
        'max_tokens' => 512,
        'seed' => 42,
        'stop' => 'END',
    ]);
});

test('toJson returns pretty printed object', function () {
    $json = ProviderGenerationOptions::toJson(['max_tokens' => 2048]);

    expect($json)->toContain('"max_tokens"')
        ->and($json)->toContain('2048');
});

test('fromRequest returns null for empty json', function () {
    expect(ProviderGenerationOptions::fromRequest(''))->toBeNull()
        ->and(ProviderGenerationOptions::fromRequest(null))->toBeNull();
});
