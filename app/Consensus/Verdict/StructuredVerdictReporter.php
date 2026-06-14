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
                verdict: $this->failureVerdict($input),
                summary: '無任何 provider 回應可抽取，未產出最終答案。',
                metadata: $metadata + ['report_type' => 'extraction_failure'],
            ),
            'Insufficient' => new VerdictReport(
                verdict: $this->singleProviderVerdict($input),
                summary: '僅單一 provider 可分析，答案未經交叉驗證。',
                metadata: $metadata + ['report_type' => 'single_provider_unverified'],
            ),
            'Majority' => new VerdictReport(
                verdict: $this->minorityReport($input),
                summary: '多數方已成立，必須呈現少數 provider 的意見。',
                metadata: $metadata + ['report_type' => 'minority_report'],
            ),
            'None' => new VerdictReport(
                verdict: $this->noConsensusReport($input),
                summary: '可分析的 provider 之間無法建立共識。',
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
        $answer = $this->representativeSummary($this->analyzableResponses($input->providerResponses));
        $lowDiscriminability = str_contains($input->consensus->status, 'low-discriminability');
        $limitations = $lowDiscriminability
            ? '僅確認未偵測到可機械比對的重大衝突。'
            : 'provider 自報引用與主張尚未經外部查證。';

        if ($this->hasPartialParticipation($input)) {
            $limitations = '部分 provider 無法分析；以下判定僅反映可分析 provider 的回應。'.$limitations;
        }

        return implode("\n", array_filter([
            '最終判定：'.$answer,
            '共識狀態：'.$input->consensus->status,
            ...$this->participationLines($input),
            '信任等級：'.$input->trustLevel->trustLevel,
            $lowDiscriminability ? '低可辨識度：無可供機械比對的 boolean、date、number 或 version 主張。' : null,
            '已知限制：'.$limitations,
        ]));
    }

    private function fullConsensusSummary(VerdictInput $input): string
    {
        if (str_contains($input->consensus->status, 'low-discriminability')) {
            return 'provider 未提供可機械比對的重大主張；未偵測到機械性重大衝突。';
        }

        return 'provider 之間未偵測到重大主張衝突。';
    }

    private function minorityReport(VerdictInput $input): string
    {
        $minorityProvider = $input->consensus->minorityProvider;
        $majorityResponses = array_values(array_filter(
            $this->analyzableResponses($input->providerResponses),
            fn (ProviderResponse $response): bool => $response->provider !== $minorityProvider,
        ));
        $minorityResponse = $this->findProvider($input->providerResponses, $minorityProvider);

        return implode("\n", [
            '多數意見：'.$this->representativeSummary($majorityResponses),
            '少數意見：'.$this->responseSummary($minorityResponse),
            '爭議主張：'.$this->disputeSummary($input),
            '證據比對：僅並陳 provider 自報引用；未對外部來源品質做出裁定。',
            '最終判定：'.$this->representativeSummary($majorityResponses),
            '信任等級：'.$input->trustLevel->trustLevel,
            '已知限制：外部查證與證據品質排序不在本 verdict reporter 的範圍內。',
        ]);
    }

    private function noConsensusReport(VerdictInput $input): string
    {
        return implode("\n", array_filter([
            '最終判定：無共識。',
            '共識狀態：None',
            '爭議主張：'.$this->disputeSummary($input),
            ...$this->participationLines($input),
            '信任等級：'.$input->trustLevel->trustLevel,
            '已知限制：reporter 不會覆寫確定性共識結果。',
        ]));
    }

    private function singleProviderVerdict(VerdictInput $input): string
    {
        return implode("\n", array_filter([
            '最終判定：'.$this->representativeSummary($this->analyzableResponses($input->providerResponses)),
            '共識狀態：Insufficient',
            ...$this->participationLines($input),
            '信任等級：'.$input->trustLevel->trustLevel,
            '已知限制：僅有一個 provider 回應可分析。',
        ]));
    }

    private function failureVerdict(VerdictInput $input): string
    {
        $absentLines = $this->absentProviderLines($input->providerResponses);

        if ($absentLines === []) {
            return '';
        }

        return implode("\n", [
            '缺席 provider：'.implode('；', $absentLines),
        ]);
    }

    /**
     * @return string[]
     */
    private function participationLines(VerdictInput $input): array
    {
        if (! $this->hasPartialParticipation($input)) {
            return [];
        }

        $analyzable = $this->analyzableResponses($input->providerResponses);
        $participating = array_map(fn (ProviderResponse $r): string => $r->provider, $analyzable);
        $absent = $this->absentProviderLines($input->providerResponses);

        $lines = [
            '參與 provider：'.implode('、', $participating).'（'.$input->context->analyzableCount.'/'.$input->context->providerCount.'）',
        ];

        if ($absent !== []) {
            $lines[] = '缺席 provider：'.implode('；', $absent);
        }

        return $lines;
    }

    private function hasPartialParticipation(VerdictInput $input): bool
    {
        return $input->context->providerCount > 0
            && $input->context->analyzableCount < $input->context->providerCount;
    }

    /**
     * @param  ProviderResponse[]  $responses
     * @return string[]
     */
    private function absentProviderLines(array $responses): array
    {
        $lines = [];

        foreach ($responses as $response) {
            if ($this->isAnalyzable($response)) {
                continue;
            }

            $lines[] = $this->absentReasonLine($response);
        }

        return $lines;
    }

    private function absentReasonLine(ProviderResponse $response): string
    {
        $label = match (true) {
            $response->providerStatus === 'failed_timeout' => '呼叫逾時',
            $response->providerStatus === 'provider_unavailable' => '提供者不可用',
            $response->providerStatus === 'provider_error' => '提供者錯誤',
            $response->extractionStatus === 'invalid_json' => 'JSON 解析失敗',
            $response->extractionStatus === 'extraction_failed' => '抽取失敗',
            default => $response->providerStatus.' / '.$response->extractionStatus,
        };

        $message = is_array($response->error) ? ($response->error['message'] ?? '') : '';

        if (is_string($message) && $message !== '') {
            return $response->provider.' — '.$label.'：'.$message;
        }

        return $response->provider.' — '.$label;
    }

    private function isAnalyzable(ProviderResponse $response): bool
    {
        return $response->providerStatus === 'success'
            && $response->extractionStatus === 'success';
    }

    /**
     * @param  ProviderResponse[]  $responses
     * @return ProviderResponse[]
     */
    private function analyzableResponses(array $responses): array
    {
        return array_values(array_filter(
            $responses,
            fn (ProviderResponse $response): bool => $this->isAnalyzable($response),
        ));
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

        return '沒有可驗證的 provider 答案。';
    }

    private function responseSummary(?ProviderResponse $response): string
    {
        if ($response === null || ! is_array($response->normalized)) {
            return '沒有 provider 摘要。';
        }

        $summary = $response->normalized['summary'] ?? null;

        return is_string($summary) && $summary !== ''
            ? $summary
            : '沒有 provider 摘要。';
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
            foreach ($this->analyzableResponses($input->providerResponses) as $response) {
                $directAnswer = $response->normalized['direct_answer'] ?? 'unknown';
                if ($directAnswer !== 'unknown') {
                    $votes[$response->provider] = $directAnswer;
                }
            }

            if (count(array_unique(array_values($votes))) > 1) {
                $parts[] = '直接回答分歧：'.$this->formatProviderMap($votes);
            }
        }

        foreach ($input->consensus->conflicts as $conflict) {
            $key = $conflict['canonical_key'] ?? $conflict['normalized_key'] ?? 'claim';
            $type = $conflict['type'] ?? 'claim';
            $providers = $conflict['providers'] ?? [];
            $parts[] = "{$type} {$key}：".$this->formatConflictProviders($providers);
        }

        return $parts === []
            ? '未偵測到重大主張衝突。'
            : implode('；', $parts);
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

        return implode('、', $formatted);
    }

    private function buildNarrativePrompt(VerdictInput $input): string
    {
        return implode("\n", [
            'Produce a concise non-binding verdict narrative in Traditional Chinese.',
            'Do not change consensus, minority provider, or trust level.',
            'Consensus: '.$input->consensus->status,
            'Trust: '.$input->trustLevel->trustLevel,
        ]);
    }
}
