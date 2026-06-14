<?php

use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\TrustLevelResult;
use App\Consensus\DTO\VerdictInput;
use App\Consensus\Verdict\StructuredVerdictReporter;

test('full 2-only verdict includes participating and absent providers in Traditional Chinese', function () {
    $reporter = new StructuredVerdictReporter;
    $input = new VerdictInput(
        classification: new ClassificationResult(type: 'B', answerShape: 'discrete', requiresGrounding: false, classifierConfidence: 'high'),
        providerResponses: [
            new ProviderResponse(
                provider: 'openai',
                providerStatus: 'failed_timeout',
                extractionStatus: 'not_started',
                error: ['message' => 'Connection timed out'],
            ),
            new ProviderResponse(
                provider: 'anthropic',
                providerStatus: 'success',
                extractionStatus: 'success',
                normalized: ['direct_answer' => 'yes', 'summary' => 'anthropic 同意。'],
            ),
            new ProviderResponse(
                provider: 'gemini',
                providerStatus: 'success',
                extractionStatus: 'success',
                normalized: ['direct_answer' => 'yes', 'summary' => 'gemini 同意。'],
            ),
        ],
        alignment: new AlignmentResult,
        consensus: new ConsensusResult(status: 'Full (2-only)'),
        trustLevel: new TrustLevelResult(trustLevel: 'Medium', base: 'High'),
        context: new AnalysisContext(providerCount: 3, analyzableCount: 2),
    );

    $report = $reporter->report($input);

    expect($report->verdict)->toContain('最終判定：')
        ->and($report->verdict)->toContain('參與 provider：anthropic、gemini（2/3）')
        ->and($report->verdict)->toContain('缺席 provider：openai — 呼叫逾時：Connection timed out')
        ->and($report->summary)->toContain('未偵測到重大主張衝突');
});

test('majority verdict uses Traditional Chinese section labels', function () {
    $reporter = new StructuredVerdictReporter;
    $input = new VerdictInput(
        classification: new ClassificationResult(type: 'B', answerShape: 'discrete', requiresGrounding: false, classifierConfidence: 'high'),
        providerResponses: [
            new ProviderResponse(provider: 'openai', providerStatus: 'success', extractionStatus: 'success', normalized: ['direct_answer' => 'yes', 'summary' => 'openai 是。']),
            new ProviderResponse(provider: 'anthropic', providerStatus: 'success', extractionStatus: 'success', normalized: ['direct_answer' => 'yes', 'summary' => 'anthropic 是。']),
            new ProviderResponse(provider: 'gemini', providerStatus: 'success', extractionStatus: 'success', normalized: ['direct_answer' => 'no', 'summary' => 'gemini 否。']),
        ],
        alignment: new AlignmentResult,
        consensus: new ConsensusResult(status: 'Majority', minorityProvider: 'gemini'),
        trustLevel: new TrustLevelResult(trustLevel: 'Medium', base: 'High'),
        context: new AnalysisContext(providerCount: 3, analyzableCount: 3),
    );

    $report = $reporter->report($input);

    expect($report->verdict)->toContain('多數意見：')
        ->and($report->verdict)->toContain('少數意見：')
        ->and($report->verdict)->toContain('最終判定：')
        ->and($report->metadata['has_minority_report'])->toBeTrue();
});
