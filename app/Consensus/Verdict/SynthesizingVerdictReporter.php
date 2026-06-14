<?php

namespace App\Consensus\Verdict;

use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\DTO\VerdictInput;
use App\Consensus\DTO\VerdictReport;
use App\Consensus\Synthesis\VerdictSynthesizer;

class SynthesizingVerdictReporter implements VerdictReporter
{
    public function __construct(
        private readonly StructuredVerdictReporter $templateReporter,
        private readonly VerdictSynthesizer $synthesizer,
    ) {}

    public function report(VerdictInput $input): VerdictReport
    {
        $template = $this->templateReporter->report($input);

        if ($input->synthesis === null || ! $input->synthesis->enabled) {
            return new VerdictReport(
                verdict: $template->verdict,
                summary: $template->summary,
                metadata: array_merge($template->metadata, [
                    'synthesis_enabled' => false,
                    'synthesis_used' => false,
                    'llm_output_used' => false,
                ]),
            );
        }

        $synthesized = $this->synthesizer->synthesize(
            questionText: $input->questionText,
            input: $input,
            request: $input->synthesis,
        );

        if ($synthesized === null || trim($synthesized) === '') {
            return new VerdictReport(
                verdict: $template->verdict,
                summary: $template->summary,
                metadata: array_merge($template->metadata, [
                    'synthesis_enabled' => true,
                    'synthesis_used' => false,
                    'synthesis_fallback' => 'template',
                    'synthesizer_slot' => $input->synthesis->synthesizerSlot,
                    'llm_output_used' => false,
                ]),
            );
        }

        return new VerdictReport(
            verdict: trim($synthesized),
            summary: $this->summaryFromSynthesis($synthesized, $template->summary),
            metadata: array_merge($template->metadata, [
                'synthesis_enabled' => true,
                'synthesis_used' => true,
                'synthesizer_slot' => $input->synthesis->synthesizerSlot,
                'llm_output_used' => true,
                'non_binding' => true,
                'synthesis_fallback' => null,
                'template_verdict' => $template->verdict,
            ]),
        );
    }

    private function summaryFromSynthesis(string $synthesized, string $fallback): string
    {
        $lines = array_values(array_filter(explode("\n", trim($synthesized))));

        if ($lines === []) {
            return $fallback;
        }

        $first = trim($lines[0]);

        return str_starts_with($first, '最終答案：') || str_starts_with($first, '最終判定：')
            ? $first
            : $fallback;
    }
}
