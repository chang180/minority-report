<?php

use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\Scorer\CascadeTrustLevelScorer;

// Helper to build a standard Type B classification
function typeB(string $answerShape = 'discrete'): ClassificationResult
{
    return new ClassificationResult(type: 'B', answerShape: $answerShape, requiresGrounding: false, classifierConfidence: 'high');
}

function typeC(): ClassificationResult
{
    return new ClassificationResult(type: 'C', answerShape: 'discrete', requiresGrounding: true, classifierConfidence: 'high');
}

function analysisCtx(int $analyzable, int $effectiveVotes = -1, bool $grounding = false): AnalysisContext
{
    return new AnalysisContext(
        groundingAvailable: $grounding,
        analyzableCount: $analyzable,
        effectiveVoteCount: $effectiveVotes,
    );
}

function consensus(string $status, array $conflicts = []): ConsensusResult
{
    return new ConsensusResult(status: $status, conflicts: $conflicts);
}

// ── §4.1 Baseline rows ─────────────────────────────────────────────────────

test('F1: Full, 3 analyzable, 3 votes → High', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Full'), analysisCtx(3, 3)
    );

    expect($result->trustLevel)->toBe('High')
        ->and($result->base)->toBe('High')
        ->and($result->caps)->toBeEmpty();
});

test('F13: Full, 3 analyzable, 2 effective votes → Medium (effective_direct_answer_vote_count_eq_2 cap)', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Full'), analysisCtx(3, 2)
    );

    expect($result->trustLevel)->toBe('Medium')
        ->and($result->base)->toBe('High')
        ->and($result->analyzableProviderCount)->toBe(3)
        ->and($result->effectiveDirectAnswerVoteCount)->toBe(2);

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('effective_direct_answer_vote_count_eq_2')
        ->and($conditions)->not->toContain('analyzable_provider_count_eq_2');
});

test('F5: Full (2-only), 2 analyzable → Medium (analyzable_provider_count_eq_2 cap)', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Full (2-only)'), analysisCtx(2, 2)
    );

    expect($result->trustLevel)->toBe('Medium')
        ->and($result->base)->toBe('High')
        ->and($result->analyzableProviderCount)->toBe(2);

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('analyzable_provider_count_eq_2');
});

test('F9: Full (low-discriminability), 3 analyzable → Medium (open_low_discriminability cap)', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB('open'), consensus('Full (low-discriminability)'), analysisCtx(3)
    );

    expect($result->trustLevel)->toBe('Medium')
        ->and($result->base)->toBe('High');

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('open_low_discriminability');
});

test('F2: Majority, 3 analyzable, no claim conflict → Medium', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Majority'), analysisCtx(3, 3)
    );

    expect($result->trustLevel)->toBe('Medium')
        ->and($result->base)->toBe('Medium')
        ->and($result->caps)->toBeEmpty();
});

test('F7: Majority, 3 analyzable + major claim conflict → Low', function () {
    $conflicts = [['type' => 'date', 'kind' => '2v1', 'minority' => 'gemini', 'canonical_key' => 'release date', 'normalized_key' => 'release date', 'providers' => []]];

    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Majority', $conflicts), analysisCtx(3, 3)
    );

    expect($result->trustLevel)->toBe('Low')
        ->and($result->base)->toBe('Medium');

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('major_claim_conflict');
});

test('F3: None, 3 analyzable → Low', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('None'), analysisCtx(3, 3)
    );

    expect($result->trustLevel)->toBe('Low')
        ->and($result->base)->toBe('Low');
});

test('F10: Insufficient, 1 analyzable → Unknown', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Insufficient'), analysisCtx(1)
    );

    expect($result->trustLevel)->toBe('Unknown')
        ->and($result->base)->toBe('Unknown');
});

test('F11: Failure, 0 analyzable → Unknown', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Failure'), analysisCtx(0)
    );

    expect($result->trustLevel)->toBe('Unknown')
        ->and($result->base)->toBe('Unknown');
});

// ── §4.2 Type C overrides ─────────────────────────────────────────────────

test('F4: Full, Type C, no grounding → Low (C cap overrides High)', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeC(), consensus('Full'), analysisCtx(3, 3)
    );

    expect($result->trustLevel)->toBe('Low')
        ->and($result->base)->toBe('High');

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('type_c_no_grounding');
});

test('Type C + Full (2-only): min(Medium, Low) → Low', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeC(), consensus('Full (2-only)'), analysisCtx(2, 2)
    );

    expect($result->trustLevel)->toBe('Low');

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('type_c_no_grounding')
        ->and($conditions)->toContain('analyzable_provider_count_eq_2');
});

test('Type C + Majority: → Low', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeC(), consensus('Majority'), analysisCtx(3, 3)
    );

    expect($result->trustLevel)->toBe('Low');
});

test('Type C + None: → Low', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeC(), consensus('None'), analysisCtx(3)
    );

    expect($result->trustLevel)->toBe('Low');
});

// ── §4.3 Composite cap examples ───────────────────────────────────────────

test('F12: N=2 + major claim conflict → min(Medium, Low) = Low', function () {
    $conflicts = [['type' => 'boolean', 'kind' => '1v1', 'minority' => null, 'canonical_key' => 'key', 'normalized_key' => 'key', 'providers' => []]];

    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('None', $conflicts), analysisCtx(2)
    );

    expect($result->trustLevel)->toBe('Low');

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->toContain('analyzable_provider_count_eq_2')
        ->and($conditions)->toContain('major_claim_conflict');
});

test('F13 distinct from F5: analyzable=3 but effectiveVotes=2 does NOT trigger analyzable_provider_count_eq_2', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Full'), analysisCtx(3, 2)
    );

    $conditions = array_column($result->caps, 'condition');
    expect($conditions)->not->toContain('analyzable_provider_count_eq_2')
        ->and($conditions)->toContain('effective_direct_answer_vote_count_eq_2');
});

test('F14: N=3 + major claim conflict → Low', function () {
    $conflicts = [['type' => 'date', 'kind' => '2v1', 'minority' => 'openai', 'canonical_key' => 'key', 'normalized_key' => 'key', 'providers' => []]];

    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('None', $conflicts), analysisCtx(3)
    );

    expect($result->trustLevel)->toBe('Low');
});

// ── audit trail ───────────────────────────────────────────────────────────

test('TrustLevelResult preserves base, both counts and applied_caps for audit trail', function () {
    $result = (new CascadeTrustLevelScorer)->score(
        typeB(), consensus('Full'), analysisCtx(3, 2)
    );

    expect($result->base)->toBe('High')
        ->and($result->analyzableProviderCount)->toBe(3)
        ->and($result->effectiveDirectAnswerVoteCount)->toBe(2)
        ->and($result->caps[0])->toMatchArray(['condition' => 'effective_direct_answer_vote_count_eq_2', 'cap' => 'Medium']);
});
