<?php

use App\Consensus\Extractor\ClaimStatementParser;

test('parses mercury melting point from traditional chinese text', function () {
    $parser = new ClaimStatementParser;

    $parsed = $parser->parseNumericStatement('水銀的標準熔點約為攝氏零下38.83度。');

    expect($parsed)->not->toBeNull()
        ->and($parsed['type'])->toBe('number')
        ->and($parsed['value'])->toBe('-38.83')
        ->and($parsed['unit'])->toBe('°C')
        ->and($parsed['predicate'])->toBe('熔點')
        ->and($parsed['canonical_key'])->toContain('水銀');
});

test('parses numeric claim embedded in latex-like formatting', function () {
    $parser = new ClaimStatementParser;

    $parsed = $parser->parseNumericStatement('水銀（Mercury, Hg）的熔點是 $356.7$ 攝氏度。');

    expect($parsed)->not->toBeNull()
        ->and($parsed['value'])->toBe('356.7')
        ->and($parsed['unit'])->toBe('°C');
});

test('normalizes 溶點 to 熔點 in canonical key', function () {
    $parser = new ClaimStatementParser;

    $parsed = $parser->parseNumericStatement('水銀的溶點是攝氏零下38.83度');

    expect($parsed['predicate'])->toBe('熔點')
        ->and($parsed['canonical_key'])->toBe('水銀 熔點');
});
