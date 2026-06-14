<?php

use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\TrustLevelResult;
use App\Consensus\DTO\VerdictInput;
use App\Consensus\Synthesis\SynthesisRequest;
use App\Consensus\Synthesis\VerdictSynthesizer;
use App\Consensus\Verdict\StructuredVerdictReporter;
use App\Consensus\Verdict\SynthesizingVerdictReporter;
use App\Consensus\Contracts\LlmProvider;

test('synthesizing reporter falls back to template when synthesis is disabled', function () {
    $templateReporter = new StructuredVerdictReporter;
    $synthesizer = Mockery::mock(VerdictSynthesizer::class);
    $synthesizer->shouldNotReceive('synthesize');

    $reporter = new SynthesizingVerdictReporter($templateReporter, $synthesizer);
    $input = verdictInputForSynthesis(synthesis: null);

    $report = $reporter->report($input);

    expect($report->metadata['synthesis_enabled'])->toBeFalse()
        ->and($report->metadata['synthesis_used'])->toBeFalse()
        ->and($report->metadata['llm_output_used'])->toBeFalse()
        ->and($report->verdict)->toContain('最終判定：');
});

test('synthesizing reporter uses AI narrative when synthesis succeeds', function () {
    $templateReporter = new StructuredVerdictReporter;
    $synthesizer = Mockery::mock(VerdictSynthesizer::class);
    $synthesizer->shouldReceive('synthesize')
        ->once()
        ->andReturn("最終答案：多數為 yes\n共識說明：兩席一致。");

    $provider = Mockery::mock(LlmProvider::class);
    $provider->shouldReceive('name')->andReturn('gemini');

    $reporter = new SynthesizingVerdictReporter($templateReporter, $synthesizer);
    $input = verdictInputForSynthesis(synthesis: new SynthesisRequest(
        enabled: true,
        synthesizerSlot: 'gemini',
        synthesizerProvider: $provider,
    ));

    $report = $reporter->report($input);

    expect($report->metadata['synthesis_enabled'])->toBeTrue()
        ->and($report->metadata['synthesis_used'])->toBeTrue()
        ->and($report->metadata['llm_output_used'])->toBeTrue()
        ->and($report->metadata['template_verdict'])->toContain('最終判定：')
        ->and($report->verdict)->toBe("最終答案：多數為 yes\n共識說明：兩席一致。")
        ->and($report->summary)->toBe('最終答案：多數為 yes');
});

test('synthesizing reporter falls back to template when synthesis returns empty', function () {
    $templateReporter = new StructuredVerdictReporter;
    $synthesizer = Mockery::mock(VerdictSynthesizer::class);
    $synthesizer->shouldReceive('synthesize')->once()->andReturn(null);

    $provider = Mockery::mock(LlmProvider::class);
    $provider->shouldReceive('name')->andReturn('gemini');

    $reporter = new SynthesizingVerdictReporter($templateReporter, $synthesizer);
    $input = verdictInputForSynthesis(synthesis: new SynthesisRequest(
        enabled: true,
        synthesizerSlot: 'gemini',
        synthesizerProvider: $provider,
    ));

    $report = $reporter->report($input);

    expect($report->metadata['synthesis_enabled'])->toBeTrue()
        ->and($report->metadata['synthesis_used'])->toBeFalse()
        ->and($report->metadata['synthesis_fallback'])->toBe('template')
        ->and($report->verdict)->toContain('最終判定：');
});

function verdictInputForSynthesis(?SynthesisRequest $synthesis): VerdictInput
{
    return new VerdictInput(
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
        questionText: '測試問題',
        synthesis: $synthesis,
    );
}
