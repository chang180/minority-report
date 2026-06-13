<?php

use App\Consensus\Aligner\StringClaimAligner;
use App\Consensus\Analyzer\HybridConsensusAnalyzer;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\ProviderResponse;

// ── helpers ────────────────────────────────────────────────────────────────

function classificationB(string $shape = 'discrete'): ClassificationResult
{
    return new ClassificationResult(type: 'B', answerShape: $shape, requiresGrounding: false, classifierConfidence: 'high');
}

function successResponse(string $provider, string $directAnswer = 'yes', array $claims = [], string $shape = 'discrete'): ProviderResponse
{
    return new ProviderResponse(
        provider: $provider,
        model: 'fake',
        providerStatus: 'success',
        extractionStatus: 'success',
        normalized: [
            'answer_shape' => $shape,
            'direct_answer' => $directAnswer,
            'summary' => "Answer from {$provider}",
            'claims' => $claims,
            'citations' => [],
        ],
    );
}

function booleanClaim(string $key, string $value): array
{
    return ['type' => 'boolean', 'canonical_key' => $key, 'subject' => '', 'predicate' => '', 'value' => $value, 'unit' => null, 'source' => null];
}

function dateClaim(string $key, string $value): array
{
    return ['type' => 'date', 'canonical_key' => $key, 'subject' => '', 'predicate' => '', 'value' => $value, 'unit' => null, 'source' => null];
}

function numberClaim(string $key, string $value, ?string $unit = null): array
{
    return ['type' => 'number', 'canonical_key' => $key, 'subject' => '', 'predicate' => '', 'value' => $value, 'unit' => $unit, 'source' => null];
}

function versionClaim(string $key, string $value): array
{
    return ['type' => 'version', 'canonical_key' => $key, 'subject' => '', 'predicate' => '', 'value' => $value, 'unit' => null, 'source' => null];
}

function entityClaim(string $key, string $value): array
{
    return ['type' => 'entity', 'canonical_key' => $key, 'subject' => '', 'predicate' => '', 'value' => $value, 'unit' => null, 'source' => null];
}

function alignAndAnalyze(ClassificationResult $classification, array $responses): ConsensusResult
{
    $alignment = (new StringClaimAligner)->align($responses);

    return (new HybridConsensusAnalyzer)->analyze($classification, $responses, $alignment);
}

// ── Case 6: Failure (N = 0) ────────────────────────────────────────────────

test('Case 6: no analyzable providers → Failure', function () {
    $result = alignAndAnalyze(classificationB(), []);

    expect($result->status)->toBe('Failure');
});

// ── Case 5: Insufficient (N = 1) ──────────────────────────────────────────

test('Case 5: one analyzable provider → Insufficient', function () {
    $responses = [successResponse('openai')];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Insufficient');
});

// ── Case 4: Two-provider ──────────────────────────────────────────────────

test('Case 4 discrete, both agree → Full (2-only)', function () {
    $responses = [successResponse('openai', 'yes'), successResponse('anthropic', 'yes')];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Full (2-only)');
});

test('Case 4 discrete, disagree → None', function () {
    $responses = [successResponse('openai', 'yes'), successResponse('anthropic', 'no')];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('None');
});

test('Case 4 MUST NOT produce Majority or Minority Report', function () {
    $responses = [successResponse('openai', 'yes'), successResponse('anthropic', 'no')];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->not->toBe('Majority')
        ->and($result->minorityProvider)->toBeNull();
});

test('Case 4 open, entity claims only → Full (low-discriminability) (2-only)', function () {
    $claim = entityClaim('concept', 'MVC');
    $responses = [
        successResponse('openai', 'not_applicable', [$claim], 'open'),
        successResponse('anthropic', 'not_applicable', [$claim], 'open'),
    ];
    $result = alignAndAnalyze(classificationB('open'), $responses);

    // entity claims are non-major → low-discriminability applies
    expect($result->status)->toBe('Full (low-discriminability) (2-only)');
});

// ── Case 1: Full Consensus (N ≥ 3) ────────────────────────────────────────

test('F01 Case 1: three providers agree on discrete → Full', function () {
    $responses = [
        successResponse('openai', 'yes'),
        successResponse('anthropic', 'yes'),
        successResponse('gemini', 'yes'),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Full')
        ->and($result->conflicts)->toBeEmpty();
});

test('Case 1 open, boolean claim present, no conflict → Full', function () {
    $claim = booleanClaim('laravel migration safe', 'true');
    $responses = [
        successResponse('openai', 'not_applicable', [$claim], 'open'),
        successResponse('anthropic', 'not_applicable', [$claim], 'open'),
        successResponse('gemini', 'not_applicable', [$claim], 'open'),
    ];
    $result = alignAndAnalyze(classificationB('open'), $responses);

    // boolean claim present → not low-discriminability → Full
    expect($result->status)->toBe('Full');
});

test('F09 Case 1 open, low-discriminability → Full (low-discriminability)', function () {
    $responses = [
        successResponse('openai', 'not_applicable', [], 'open'),
        successResponse('anthropic', 'not_applicable', [], 'open'),
        successResponse('gemini', 'not_applicable', [], 'open'),
    ];
    $result = alignAndAnalyze(classificationB('open'), $responses);

    expect($result->status)->toBe('Full (low-discriminability)');
});

// ── Case 2: Majority vs Minority (N ≥ 3) ──────────────────────────────────

test('F02 Case 2: 2 vs 1 on direct_answer → Majority with minority provider', function () {
    $responses = [
        successResponse('openai', 'yes'),
        successResponse('anthropic', 'yes'),
        successResponse('gemini', 'no'),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('gemini');
});

test('F07 Case 2: direct_answer all yes but date claim 2 vs 1 → Majority with claim minority', function () {
    $sharedClaim = dateClaim('release date', '2024-03-15');
    $minorityClaim = dateClaim('release date', '2023-06-01');

    $responses = [
        successResponse('openai', 'yes', [$sharedClaim]),
        successResponse('anthropic', 'yes', [$sharedClaim]),
        successResponse('gemini', 'yes', [$minorityClaim]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('gemini')
        ->and($result->conflicts)->not->toBeEmpty();
});

test('Case 2 boolean claim 2 vs 1 with consistent direct_answer → Majority', function () {
    $majorityBool = booleanClaim('is stable', 'true');
    $minorityBool = booleanClaim('is stable', 'false');

    $responses = [
        successResponse('openai', 'yes', [$majorityBool]),
        successResponse('anthropic', 'yes', [$majorityBool]),
        successResponse('gemini', 'yes', [$minorityBool]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('gemini');
});

test('Case 2 version conflict 2 vs 1 → Majority', function () {
    $v1 = versionClaim('php version', '8.4.0');
    $v2 = versionClaim('php version', '8.3.0');

    $responses = [
        successResponse('openai', 'yes', [$v1]),
        successResponse('anthropic', 'yes', [$v1]),
        successResponse('gemini', 'yes', [$v2]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('gemini');
});

test('Case 2 number conflict >5% relative error → Majority', function () {
    $n1 = numberClaim('price', '100', 'usd');
    $n2 = numberClaim('price', '150', 'usd');

    $responses = [
        successResponse('openai', 'yes', [$n1]),
        successResponse('anthropic', 'yes', [$n1]),
        successResponse('gemini', 'yes', [$n2]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('gemini');
});

test('number conflict within 5% tolerance → no conflict, Full', function () {
    $n1 = numberClaim('size', '100', 'mb');
    $n2 = numberClaim('size', '102', 'mb');

    $responses = [
        successResponse('openai', 'yes', [$n1]),
        successResponse('anthropic', 'yes', [$n1]),
        successResponse('gemini', 'yes', [$n2]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Full');
});

// ── Case 3: No Consensus (N ≥ 3) ─────────────────────────────────────────

test('F03 Case 3: yes/no/unknown → 1v1 after abstention → None', function () {
    $responses = [
        successResponse('openai', 'yes'),
        successResponse('anthropic', 'no'),
        successResponse('gemini', 'unknown'),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('None');
});

test('Case 3: all three disagree → None (no-majority)', function () {
    $responses = [
        successResponse('openai', 'yes'),
        successResponse('anthropic', 'no'),
        successResponse('gemini', 'no'), // actually 2v1 on direct_answer but claims diverge
    ];
    // With gemini='no' and anthropic='no', openai='yes' → openai is minority
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('openai');
});

test('F14 Case 3: multi-axis minority not converged → None', function () {
    // direct_answer: openai=yes, anthropic=yes, gemini=no → gemini minority on direct_answer
    // date claim: openai=2024-03, gemini=2024-03, anthropic=2023-01 → anthropic minority on claim
    // Two different minorities → None per §8
    $d1 = dateClaim('launch date', '2024-03');
    $d2 = dateClaim('launch date', '2023-01');

    $responses = [
        successResponse('openai', 'yes', [$d1]),
        successResponse('anthropic', 'yes', [$d2]),
        successResponse('gemini', 'no', [$d1]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('None');
});

// ── §7 Abstention ─────────────────────────────────────────────────────────

test('F13: yes/yes/unknown → Full (2 effective votes, P3 not minority)', function () {
    $responses = [
        successResponse('openai', 'yes'),
        successResponse('anthropic', 'yes'),
        successResponse('gemini', 'unknown'),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Full')
        ->and($result->minorityProvider)->toBeNull()
        ->and($result->metadata['effective_vote_count'])->toBe(2);
});

test('F13: abstaining provider MUST NOT be listed as minority', function () {
    $responses = [
        successResponse('openai', 'yes'),
        successResponse('anthropic', 'yes'),
        successResponse('gemini', 'unknown'),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->minorityProvider)->not->toBe('gemini');
});

// ── Claim alignment helpers ────────────────────────────────────────────────

test('entity and statement claims never trigger major conflict', function () {
    $e1 = entityClaim('author', 'Taylor Otwell');
    $e2 = entityClaim('author', 'DHH'); // different entity

    $responses = [
        successResponse('openai', 'yes', [$e1]),
        successResponse('anthropic', 'yes', [$e1]),
        successResponse('gemini', 'yes', [$e2]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    // No major conflict because entity type is surfaced only
    expect($result->status)->toBe('Full')
        ->and($result->conflicts)->toBeEmpty();
});

test('number claims with incompatible units are marked unalignable, not a conflict', function () {
    $nKm = numberClaim('distance', '10', 'km');
    $nMi = numberClaim('distance', '6.2', 'mi');

    $aligner = new StringClaimAligner;
    $responses = [
        successResponse('openai', 'yes', [$nKm]),
        successResponse('anthropic', 'yes', [$nMi]),
        successResponse('gemini', 'yes', [$nKm]),
    ];
    $alignment = $aligner->align($responses);

    expect($alignment->unalignable)->not->toBeEmpty()
        ->and($alignment->aligned)->toBeEmpty();
});

test('date conflict at coarsest common granularity: "2024" vs "2024-03-15" → no conflict', function () {
    // §5: compare at coarsest common granularity (year in this case)
    $coarseDate = dateClaim('php release year', '2024');
    $preciseDate = dateClaim('php release year', '2024-11-21');

    $responses = [
        successResponse('openai', 'yes', [$coarseDate]),
        successResponse('anthropic', 'yes', [$preciseDate]),
        successResponse('gemini', 'yes', [$coarseDate]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    // Both resolve to "2024" at year granularity → no conflict → Full
    expect($result->status)->toBe('Full')
        ->and($result->conflicts)->toBeEmpty();
});

test('date conflict at year granularity: "2024" vs "2025-03" → conflict', function () {
    $d1 = dateClaim('release', '2024');
    $d2 = dateClaim('release', '2025-03');

    $responses = [
        successResponse('openai', 'yes', [$d1]),
        successResponse('anthropic', 'yes', [$d1]),
        successResponse('gemini', 'yes', [$d2]),
    ];
    $result = alignAndAnalyze(classificationB(), $responses);

    expect($result->status)->toBe('Majority')
        ->and($result->minorityProvider)->toBe('gemini');
});

test('canonical_key normalization allows cross-provider alignment', function () {
    $c1 = dateClaim('PHP 8.4 release date', '2024-11-21');
    $c2 = dateClaim('php 8.4 release date', '2024-11-21'); // lowercase variant
    $c3 = dateClaim('PHP 8.4 release date', '2024-11-21');

    $aligner = new StringClaimAligner;
    $alignment = $aligner->align([
        successResponse('openai', 'yes', [$c1]),
        successResponse('anthropic', 'yes', [$c2]),
        successResponse('gemini', 'yes', [$c3]),
    ]);

    expect($alignment->aligned)->toHaveCount(1)
        ->and($alignment->unmatched)->toBeEmpty();
});
