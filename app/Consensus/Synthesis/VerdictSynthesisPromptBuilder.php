<?php

namespace App\Consensus\Synthesis;

use App\Consensus\DTO\ProviderResponse;
use App\Consensus\DTO\VerdictInput;

class VerdictSynthesisPromptBuilder
{
    public function build(string $questionText, VerdictInput $input): string
    {
        $witnesses = $this->witnessPayload($input->providerResponses);
        $payload = [
            'question' => $questionText,
            'answer_shape' => $input->classification->answerShape,
            'deterministic_results' => [
                'consensus_status' => $input->consensus->status,
                'trust_level' => $input->trustLevel->trustLevel,
                'minority_provider' => $input->consensus->minorityProvider,
                'majority_provider' => $input->consensus->majorityProvider,
                'conflicts' => $input->consensus->conflicts,
            ],
            'alignment' => [
                'aligned' => $input->alignment->aligned,
                'unmatched' => $input->alignment->unmatched,
                'unalignable' => $input->alignment->unalignable,
            ],
            'witness_providers' => $witnesses,
            'participation' => $input->context->metadata['provider_statuses'] ?? [],
        ];

        return implode("\n", [
            'Write the final verification report in Traditional Chinese.',
            'The deterministic consensus results below are authoritative — do not contradict them.',
            '',
            json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT),
        ]);
    }

    /**
     * @param  ProviderResponse[]  $providerResponses
     * @return array<int, array<string, mixed>>
     */
    private function witnessPayload(array $providerResponses): array
    {
        return array_values(array_map(
            fn (ProviderResponse $response): array => [
                'provider' => $response->provider,
                'provider_status' => $response->providerStatus,
                'extraction_status' => $response->extractionStatus,
                'summary' => $response->normalized['summary'] ?? null,
                'direct_answer' => $response->normalized['direct_answer'] ?? null,
                'claims' => $response->normalized['claims'] ?? [],
                'error' => $response->error,
            ],
            $providerResponses,
        ));
    }
}
