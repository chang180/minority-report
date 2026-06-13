<?php

namespace App\Consensus\Scorer;

use App\Consensus\Contracts\TrustLevelScorer;
use App\Consensus\DTO\AnalysisContext;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\TrustLevelResult;

class CascadeTrustLevelScorer implements TrustLevelScorer
{
    /** @var array<string, int> */
    private const LEVEL_ORDER = [
        'Unknown' => 0,
        'Low' => 1,
        'Medium' => 2,
        'High' => 3,
    ];

    public function score(
        ClassificationResult $classification,
        ConsensusResult $consensus,
        AnalysisContext $context,
    ): TrustLevelResult {
        $base = $this->computeBase($consensus->status);
        $caps = $this->collectCaps($classification, $consensus, $context);

        $finalLevel = $base;
        foreach ($caps as $cap) {
            $finalLevel = $this->min($finalLevel, $cap['cap']);
        }

        return new TrustLevelResult(
            trustLevel: $finalLevel,
            base: $base,
            analyzableProviderCount: $context->analyzableCount,
            effectiveDirectAnswerVoteCount: $context->effectiveVoteCount,
            caps: $caps,
        );
    }

    private function computeBase(string $consensusStatus): string
    {
        return match ($consensusStatus) {
            'Full', 'Full (2-only)', 'Full (low-discriminability)' => 'High',
            'Full (low-discriminability) (2-only)' => 'High',
            'Majority' => 'Medium',
            'None' => 'Low',
            'Insufficient', 'Failure' => 'Unknown',
            default => 'Unknown',
        };
    }

    /**
     * Collect all applicable caps per doc §2 cap table.
     *
     * @return array<int, array{condition: string, cap: string}>
     */
    private function collectCaps(
        ClassificationResult $classification,
        ConsensusResult $consensus,
        AnalysisContext $context,
    ): array {
        $caps = [];

        // Type C + no grounding
        if ($classification->type === 'C' && ! $context->groundingAvailable) {
            $caps[] = ['condition' => 'type_c_no_grounding', 'cap' => 'Low'];
        }

        // Analyzable provider count == 2 (Case 4 / F05)
        if ($context->analyzableCount === 2) {
            $caps[] = ['condition' => 'analyzable_provider_count_eq_2', 'cap' => 'Medium'];
        }

        // Effective direct_answer vote count == 2 (discrete; F13)
        // Only applicable when effectiveVoteCount is set (>= 0) and == 2
        if ($context->effectiveVoteCount === 2) {
            $caps[] = ['condition' => 'effective_direct_answer_vote_count_eq_2', 'cap' => 'Medium'];
        }

        // Major claim conflict exists
        if (count($consensus->conflicts) > 0) {
            $caps[] = ['condition' => 'major_claim_conflict', 'cap' => 'Low'];
        }

        // Open + low-discriminability
        $isLowDisc = str_contains($consensus->status, 'low-discriminability');
        if ($isLowDisc) {
            $caps[] = ['condition' => 'open_low_discriminability', 'cap' => 'Medium'];
        }

        // Consensus = None
        if ($consensus->status === 'None') {
            $caps[] = ['condition' => 'consensus_none', 'cap' => 'Low'];
        }

        // Consensus = Insufficient or Failure
        if ($consensus->status === 'Insufficient' || $consensus->status === 'Failure') {
            $caps[] = ['condition' => 'consensus_insufficient_or_failure', 'cap' => 'Unknown'];
        }

        return $caps;
    }

    private function min(string $a, string $b): string
    {
        $orderA = self::LEVEL_ORDER[$a] ?? 0;
        $orderB = self::LEVEL_ORDER[$b] ?? 0;

        return $orderA <= $orderB ? $a : $b;
    }
}
