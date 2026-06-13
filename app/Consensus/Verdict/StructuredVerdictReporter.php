<?php

namespace App\Consensus\Verdict;

use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\VerdictInput;
use App\Consensus\DTO\VerdictReport;

class StructuredVerdictReporter implements VerdictReporter
{
    public function report(VerdictInput $input): VerdictReport
    {
        $metadata = [
            'non_binding' => true,
            'deterministic_consensus_status' => $input->consensus->status,
            'has_minority_report' => $input->consensus->status === 'Majority' && $input->consensus->minorityProvider !== null,
            'llm_prompt' => $this->buildNarrativePrompt($input),
            'llm_output_used' => false,
        ];

        return match ($input->consensus->status) {
            'Failure' => new VerdictReport(
                verdict: '',
                summary: 'No final answer was produced because no provider response could be extracted.',
                metadata: $metadata + ['report_type' => 'extraction_failure'],
            ),
            'Insufficient' => new VerdictReport(
                verdict: $this->singleProviderVerdict($input),
                summary: 'Single Provider Answer - Unverified.',
                metadata: $metadata + ['report_type' => 'single_provider_unverified'],
            ),
            'Majority' => new VerdictReport(
                verdict: $this->minorityReport($input),
                summary: 'A majority answer exists, with a minority provider that must be surfaced.',
                metadata: $metadata + ['report_type' => 'minority_report'],
            ),
            'None' => new VerdictReport(
                verdict: $this->noConsensusReport($input),
                summary: 'No consensus could be established across the analyzable providers.',
                metadata: $metadata + ['report_type' => 'no_consensus'],
            ),
            default => new VerdictReport(
                verdict: $this->fullConsensusReport($input),
                summary: $this->fullConsensusSummary($input),
                metadata: $metadata + ['report_type' => 'consensus'],
            ),
        };
    }

    private function fullConsensusReport(VerdictInput $input): string
    {
        $answer = $this->representativeSummary($input->providerResponses);
        $lowDiscriminability = str_contains($input->consensus->status, 'low-discriminability');
        $limitations = $lowDiscriminability
            ? 'Only the absence of mechanically comparable major conflicts was confirmed.'
            : 'Provider citations and claims were not externally grounded.';

        return implode("\n", array_filter([
            'Final Verdict: '.$answer,
            'Consensus: '.$input->consensus->status,
            'Trust Level: '.$input->trustLevel->trustLevel,
            $lowDiscriminability ? 'Low-Discriminability: no boolean, date, number, or version claim was available for mechanical comparison.' : null,
            'Known Limitations: '.$limitations,
        ]));
    }

    private function fullConsensusSummary(VerdictInput $input): string
    {
        if (str_contains($input->consensus->status, 'low-discriminability')) {
            return 'Providers did not expose mechanically comparable major claims; no mechanical major conflict was detected.';
        }

        return 'Providers converge without detected major claim conflicts.';
    }

    private function minorityReport(VerdictInput $input): string
    {
        $minorityProvider = $input->consensus->minorityProvider;
        $majorityResponses = array_values(array_filter(
            $input->providerResponses,
            fn (ProviderResponse $response): bool => $response->extractionStatus === 'success'
                && $response->provider !== $minorityProvider,
        ));
        $minorityResponse = $this->findProvider($input->providerResponses, $minorityProvider);

        return implode("\n", [
            'Majority Opinion: '.$this->representativeSummary($majorityResponses),
            'Minority Opinion: '.$this->responseSummary($minorityResponse),
            'Disputed Claims: '.$this->disputeSummary($input),
            'Evidence Comparison: Provider self-reported citations are surfaced only; no external source quality judgment was made.',
            'Final Verdict: '.$this->representativeSummary($majorityResponses),
            'Trust Level: '.$input->trustLevel->trustLevel,
            'Known Limitations: External grounding and evidence quality ranking are outside the MVP verdict reporter.',
        ]);
    }

    private function noConsensusReport(VerdictInput $input): string
    {
        return implode("\n", [
            'Final Verdict: No consensus.',
            'Consensus: None',
            'Disputed Claims: '.$this->disputeSummary($input),
            'Trust Level: '.$input->trustLevel->trustLevel,
            'Known Limitations: The reporter does not override deterministic consensus.',
        ]);
    }

    private function singleProviderVerdict(VerdictInput $input): string
    {
        return implode("\n", [
            'Final Verdict: '.$this->representativeSummary($input->providerResponses),
            'Consensus: Insufficient',
            'Trust Level: '.$input->trustLevel->trustLevel,
            'Known Limitations: Only one provider response was analyzable.',
        ]);
    }

    /**
     * @param  ProviderResponse[]  $responses
     */
    private function representativeSummary(array $responses): string
    {
        foreach ($responses as $response) {
            if ($response->extractionStatus === 'success') {
                return $this->responseSummary($response);
            }
        }

        return 'No verified provider answer is available.';
    }

    private function responseSummary(?ProviderResponse $response): string
    {
        if ($response === null || ! is_array($response->normalized)) {
            return 'No provider summary is available.';
        }

        $summary = $response->normalized['summary'] ?? null;

        return is_string($summary) && $summary !== ''
            ? $summary
            : 'No provider summary is available.';
    }

    /**
     * @param  ProviderResponse[]  $responses
     */
    private function findProvider(array $responses, ?string $provider): ?ProviderResponse
    {
        foreach ($responses as $response) {
            if ($response->provider === $provider) {
                return $response;
            }
        }

        return null;
    }

    private function disputeSummary(VerdictInput $input): string
    {
        $parts = [];

        if ($input->classification->answerShape === 'discrete') {
            $votes = [];
            foreach ($input->providerResponses as $response) {
                if ($response->extractionStatus !== 'success') {
                    continue;
                }

                $directAnswer = $response->normalized['direct_answer'] ?? 'unknown';
                if ($directAnswer !== 'unknown') {
                    $votes[$response->provider] = $directAnswer;
                }
            }

            if (count(array_unique(array_values($votes))) > 1) {
                $parts[] = 'direct_answer split: '.$this->formatProviderMap($votes);
            }
        }

        foreach ($input->consensus->conflicts as $conflict) {
            $key = $conflict['canonical_key'] ?? $conflict['normalized_key'] ?? 'claim';
            $type = $conflict['type'] ?? 'claim';
            $providers = $conflict['providers'] ?? [];
            $parts[] = "{$type} {$key}: ".$this->formatConflictProviders($providers);
        }

        return $parts === []
            ? 'No major claim conflict was detected.'
            : implode('; ', $parts);
    }

    /**
     * @param  array<string, mixed>  $providers
     */
    private function formatConflictProviders(array $providers): string
    {
        $values = [];

        foreach ($providers as $provider => $claim) {
            $values[$provider] = is_array($claim) ? ($claim['value'] ?? '') : $claim;
        }

        return $this->formatProviderMap($values);
    }

    /**
     * @param  array<string, mixed>  $values
     */
    private function formatProviderMap(array $values): string
    {
        $formatted = [];

        foreach ($values as $provider => $value) {
            $formatted[] = $provider.'='.(string) $value;
        }

        return implode(', ', $formatted);
    }

    private function buildNarrativePrompt(VerdictInput $input): string
    {
        return implode("\n", [
            'Produce a concise non-binding verdict narrative.',
            'Do not change consensus, minority provider, or trust level.',
            'Consensus: '.$input->consensus->status,
            'Trust: '.$input->trustLevel->trustLevel,
        ]);
    }
}
