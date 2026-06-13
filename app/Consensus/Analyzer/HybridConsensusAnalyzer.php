<?php

namespace App\Consensus\Analyzer;

use App\Consensus\Contracts\ConsensusAnalyzer;
use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ConsensusResult;
use App\Consensus\DTO\ProviderResponse;

class HybridConsensusAnalyzer implements ConsensusAnalyzer
{
    private const NUMBER_CONFLICT_THRESHOLD = 0.05;

    /**
     * @param  ProviderResponse[]  $analyzableResponses
     */
    public function analyze(
        ClassificationResult $classification,
        array $analyzableResponses,
        AlignmentResult $alignment,
    ): ConsensusResult {
        $n = count($analyzableResponses);

        return match (true) {
            $n === 0 => $this->case6Failure(),
            $n === 1 => $this->case5Insufficient($analyzableResponses[0]),
            $n === 2 => $this->case4TwoProvider($classification, $analyzableResponses, $alignment),
            default => $this->caseThreeOrMore($classification, $analyzableResponses, $alignment),
        };
    }

    private function case6Failure(): ConsensusResult
    {
        return new ConsensusResult(
            status: 'Failure',
            metadata: ['case' => 6],
        );
    }

    /** @param ProviderResponse[] $analyzableResponses */
    private function case5Insufficient(ProviderResponse $single): ConsensusResult
    {
        return new ConsensusResult(
            status: 'Insufficient',
            metadata: ['case' => 5, 'single_provider' => $single->provider],
        );
    }

    /** @param ProviderResponse[] $analyzableResponses */
    private function case4TwoProvider(
        ClassificationResult $classification,
        array $analyzableResponses,
        AlignmentResult $alignment,
    ): ConsensusResult {
        $claimConflicts = $this->detectMajorClaimConflicts($alignment->aligned);

        if ($classification->answerShape === 'discrete') {
            $p1 = $analyzableResponses[0];
            $p2 = $analyzableResponses[1];
            $a1 = $p1->normalized['direct_answer'] ?? 'unknown';
            $a2 = $p2->normalized['direct_answer'] ?? 'unknown';

            $directMatch = $a1 === $a2;
            $hasMajorConflict = count($claimConflicts) > 0;

            if ($directMatch && ! $hasMajorConflict) {
                return new ConsensusResult(
                    status: 'Full (2-only)',
                    conflicts: $claimConflicts,
                    metadata: ['case' => 4, 'answer_shape' => 'discrete'],
                );
            }

            return new ConsensusResult(
                status: 'None',
                conflicts: $claimConflicts,
                metadata: ['case' => 4, 'answer_shape' => 'discrete'],
            );
        }

        // open: no direct_answer voting; consensus is determined by claim conflicts
        $lowDisc = $this->isLowDiscriminability($alignment->aligned);
        $hasMajorConflict = count($claimConflicts) > 0;

        if (! $hasMajorConflict) {
            $status = $lowDisc ? 'Full (low-discriminability) (2-only)' : 'Full (2-only)';

            return new ConsensusResult(
                status: $status,
                conflicts: $claimConflicts,
                metadata: ['case' => 4, 'answer_shape' => 'open', 'low_discriminability' => $lowDisc],
            );
        }

        return new ConsensusResult(
            status: 'None',
            conflicts: $claimConflicts,
            metadata: ['case' => 4, 'answer_shape' => 'open'],
        );
    }

    /** @param ProviderResponse[] $analyzableResponses */
    private function caseThreeOrMore(
        ClassificationResult $classification,
        array $analyzableResponses,
        AlignmentResult $alignment,
    ): ConsensusResult {
        $claimConflicts = $this->detectMajorClaimConflicts($alignment->aligned);
        $hasMajorConflict = count($claimConflicts) > 0;

        if ($classification->answerShape === 'open') {
            return $this->analyzeOpenQuestion($analyzableResponses, $alignment, $claimConflicts, $hasMajorConflict);
        }

        return $this->analyzeDiscreteQuestion($analyzableResponses, $alignment, $claimConflicts, $hasMajorConflict);
    }

    /** @param ProviderResponse[] $analyzableResponses */
    private function analyzeOpenQuestion(
        array $analyzableResponses,
        AlignmentResult $alignment,
        array $claimConflicts,
        bool $hasMajorConflict,
    ): ConsensusResult {
        $lowDisc = $this->isLowDiscriminability($alignment->aligned);

        if (! $hasMajorConflict) {
            $status = $lowDisc ? 'Full (low-discriminability)' : 'Full';

            return new ConsensusResult(
                status: $status,
                conflicts: $claimConflicts,
                metadata: ['case' => 1, 'answer_shape' => 'open', 'low_discriminability' => $lowDisc],
            );
        }

        // Check if all conflicts converge to a single minority owner
        $convergence = $this->convergeSingleMinority($claimConflicts, null);

        if ($convergence['converged']) {
            return new ConsensusResult(
                status: 'Majority',
                minorityProvider: $convergence['minority'],
                conflicts: $claimConflicts,
                metadata: ['case' => 2, 'answer_shape' => 'open'],
            );
        }

        return new ConsensusResult(
            status: 'None',
            conflicts: $claimConflicts,
            metadata: ['case' => 3, 'answer_shape' => 'open'],
        );
    }

    /** @param ProviderResponse[] $analyzableResponses */
    private function analyzeDiscreteQuestion(
        array $analyzableResponses,
        AlignmentResult $alignment,
        array $claimConflicts,
        bool $hasMajorConflict,
    ): ConsensusResult {
        // Collect direct_answer votes, excluding 'unknown' abstentions
        $votes = [];
        foreach ($analyzableResponses as $response) {
            $directAnswer = $response->normalized['direct_answer'] ?? 'unknown';
            if ($directAnswer !== 'unknown') {
                $votes[$response->provider] = $directAnswer;
            }
        }

        $effectiveCount = count($votes);

        // Fewer than 2 effective votes — treat per §7 as insufficient
        if ($effectiveCount < 2) {
            return new ConsensusResult(
                status: 'Insufficient',
                conflicts: $claimConflicts,
                metadata: [
                    'case' => 5,
                    'answer_shape' => 'discrete',
                    'effective_vote_count' => $effectiveCount,
                    'abstentions' => count($analyzableResponses) - $effectiveCount,
                ],
            );
        }

        // Exactly 2 effective votes (§7: 2 consistent → Full, else None)
        if ($effectiveCount === 2) {
            $values = array_values($votes);
            $consistent = $values[0] === $values[1];

            if ($consistent && ! $hasMajorConflict) {
                return new ConsensusResult(
                    status: 'Full',
                    conflicts: $claimConflicts,
                    metadata: [
                        'case' => 1,
                        'answer_shape' => 'discrete',
                        'effective_vote_count' => 2,
                        'abstentions' => count($analyzableResponses) - 2,
                    ],
                );
            }

            return new ConsensusResult(
                status: 'None',
                conflicts: $claimConflicts,
                metadata: [
                    'case' => 3,
                    'answer_shape' => 'discrete',
                    'effective_vote_count' => 2,
                ],
            );
        }

        // 3 effective votes — determine direct_answer minority
        $directAnswerMinority = $this->findDirectAnswerMinority($votes);

        // Case 1: Full Consensus — all agree AND no major claim conflict
        if ($directAnswerMinority === null && ! $hasMajorConflict) {
            return new ConsensusResult(
                status: 'Full',
                conflicts: $claimConflicts,
                metadata: ['case' => 1, 'answer_shape' => 'discrete', 'effective_vote_count' => $effectiveCount],
            );
        }

        // No-majority on direct_answer (all three differ) → immediately None
        if ($directAnswerMinority === 'no-majority') {
            return new ConsensusResult(
                status: 'None',
                conflicts: $claimConflicts,
                metadata: ['case' => 3, 'answer_shape' => 'discrete', 'reason' => 'no_majority_direct_answer'],
            );
        }

        // Now check §8 multi-axis convergence
        // $directAnswerMinority is either null (full agreement) or a provider name
        $convergence = $this->convergeSingleMinority($claimConflicts, $directAnswerMinority);

        if (! $convergence['converged']) {
            return new ConsensusResult(
                status: 'None',
                conflicts: $claimConflicts,
                metadata: ['case' => 3, 'answer_shape' => 'discrete', 'reason' => $convergence['reason']],
            );
        }

        // Single minority owner identified across all axes
        $minority = $convergence['minority'];

        if ($minority !== null) {
            return new ConsensusResult(
                status: 'Majority',
                minorityProvider: $minority,
                conflicts: $claimConflicts,
                metadata: ['case' => 2, 'answer_shape' => 'discrete', 'effective_vote_count' => $effectiveCount],
            );
        }

        // direct_answer all agree but major claim conflict exists — check if claim conflict
        // yields a single minority (Case 2: "direct_answer consistent but claim 2 vs 1")
        if ($hasMajorConflict) {
            $claimConvergence = $this->convergeSingleMinority($claimConflicts, null);
            if ($claimConvergence['converged'] && $claimConvergence['minority'] !== null) {
                return new ConsensusResult(
                    status: 'Majority',
                    minorityProvider: $claimConvergence['minority'],
                    conflicts: $claimConflicts,
                    metadata: ['case' => 2, 'answer_shape' => 'discrete'],
                );
            }

            return new ConsensusResult(
                status: 'None',
                conflicts: $claimConflicts,
                metadata: ['case' => 3, 'answer_shape' => 'discrete', 'reason' => 'major_claim_conflict_no_single_minority'],
            );
        }

        return new ConsensusResult(
            status: 'Full',
            conflicts: $claimConflicts,
            metadata: ['case' => 1, 'answer_shape' => 'discrete'],
        );
    }

    /**
     * Returns null if all agree, provider name if 2 vs 1, 'no-majority' if all differ,
     * or '1v1' if exactly two providers with different values.
     *
     * @param  array<string, string>  $votes  [provider => answer]
     */
    private function findDirectAnswerMinority(array $votes): ?string
    {
        $grouped = [];
        foreach ($votes as $provider => $answer) {
            $grouped[$answer][] = $provider;
        }

        if (count($grouped) === 1) {
            return null; // all agree
        }

        if (count($votes) === 2) {
            return '1v1'; // two providers, different values
        }

        // 3 votes
        foreach ($grouped as $answer => $providers) {
            if (count($providers) === 1) {
                // This answer belongs to exactly one provider — minority
                return $providers[0];
            }
        }

        return 'no-majority'; // all three differ
    }

    /**
     * Check §8: all conflicts (direct_answer + claim axes) must converge to one minority.
     *
     * @param  array<int, array{type: string, minority: ?string, kind: string}>  $claimConflicts
     * @return array{converged: bool, minority: ?string, reason: string}
     */
    private function convergeSingleMinority(array $claimConflicts, ?string $directAnswerMinority): array
    {
        $allMinorities = [];

        if ($directAnswerMinority !== null && $directAnswerMinority !== 'no-majority' && $directAnswerMinority !== '1v1') {
            $allMinorities[] = $directAnswerMinority;
        }

        foreach ($claimConflicts as $conflict) {
            if ($conflict['kind'] === 'no-majority' || $conflict['kind'] === '1v1') {
                return ['converged' => false, 'minority' => null, 'reason' => 'no_majority_claim_conflict'];
            }
            if ($conflict['minority'] !== null) {
                $allMinorities[] = $conflict['minority'];
            }
        }

        $unique = array_values(array_unique($allMinorities));

        if (count($unique) === 0) {
            return ['converged' => true, 'minority' => null, 'reason' => ''];
        }

        if (count($unique) === 1) {
            return ['converged' => true, 'minority' => $unique[0], 'reason' => ''];
        }

        return ['converged' => false, 'minority' => null, 'reason' => 'conflicting_minority_owners'];
    }

    /**
     * Detect major claim conflicts (boolean/date/number/version) from aligned claims.
     *
     * @param  array<int, array{type: string, canonical_key: string, normalized_key: string, providers: array<string, array{value: string, unit: ?string}>}>  $aligned
     * @return array<int, array{type: string, canonical_key: string, normalized_key: string, minority: ?string, kind: string, providers: array<string, mixed>}>
     */
    private function detectMajorClaimConflicts(array $aligned): array
    {
        $majorTypes = ['boolean', 'date', 'number', 'version'];
        $conflicts = [];

        foreach ($aligned as $claim) {
            if (! in_array($claim['type'], $majorTypes, true)) {
                continue;
            }

            $conflict = $this->detectConflict($claim);
            if ($conflict !== null) {
                $conflicts[] = array_merge(['canonical_key' => $claim['canonical_key'], 'normalized_key' => $claim['normalized_key']], $conflict);
            }
        }

        return $conflicts;
    }

    /**
     * @param  array{type: string, providers: array<string, array{value: string, unit: ?string}>}  $claim
     * @return array{type: string, kind: string, minority: ?string, providers: array<string, mixed>}|null null = no conflict
     */
    private function detectConflict(array $claim): ?array
    {
        $type = $claim['type'];
        $providers = $claim['providers'];

        $normalizedValues = [];
        foreach ($providers as $provider => $data) {
            $normalizedValues[$provider] = $this->normalizeValue($type, $data['value'], $data['unit'] ?? null);
        }

        // For date: truncate all to coarsest common granularity before comparing (§5)
        if ($type === 'date') {
            $normalizedValues = $this->truncateDatesToCoarestGranularity($normalizedValues);
        }

        // Check if any values differ
        $unique = array_unique(array_values($normalizedValues));
        if (count($unique) === 1) {
            return null; // all agree, no conflict
        }

        // For number: check relative error threshold
        if ($type === 'number') {
            if (! $this->numberExceedsThreshold($normalizedValues)) {
                return null;
            }
        }

        $minority = $this->findMinority($normalizedValues);

        return [
            'type' => $type,
            'kind' => $minority['kind'],
            'minority' => $minority['provider'],
            'providers' => $providers,
        ];
    }

    private function normalizeValue(string $type, string $value, ?string $unit): string
    {
        return match ($type) {
            'boolean' => strtolower(trim($value)),
            'date' => $this->normalizeDate($value),
            'number' => $this->normalizeNumber($value),
            'version' => $this->normalizeVersion($value),
            default => strtolower(trim($value)),
        };
    }

    private function normalizeDate(string $value): string
    {
        $value = trim($value);
        // Support YYYY, YYYY-MM, YYYY-MM-DD
        if (preg_match('/^\d{4}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\d{4}-\d{2}$/', $value)) {
            return $value;
        }
        if (preg_match('/^\d{4}-\d{2}-\d{2}/', $value, $m)) {
            return substr($value, 0, 10);
        }

        return strtolower($value);
    }

    /**
     * Truncate all date strings to the coarsest common precision (year/month/day).
     * "2024" and "2024-03-15" → both truncated to "2024" so they compare as equal.
     *
     * @param  array<string, string>  $normalizedValues  [provider => ISO date string]
     * @return array<string, string>
     */
    private function truncateDatesToCoarestGranularity(array $normalizedValues): array
    {
        $minLen = 10;
        foreach ($normalizedValues as $v) {
            if (preg_match('/^\d{4}$/', $v)) {
                $minLen = min($minLen, 4);
            } elseif (preg_match('/^\d{4}-\d{2}$/', $v)) {
                $minLen = min($minLen, 7);
            }
        }

        return array_map(fn ($v) => substr($v, 0, $minLen), $normalizedValues);
    }

    private function normalizeNumber(string $value): string
    {
        // Strip common formatting
        $value = preg_replace('/[,\s]/', '', trim($value)) ?? $value;

        return $value;
    }

    private function normalizeVersion(string $value): string
    {
        $value = ltrim(trim($value), 'vV');
        // Pad to major.minor.patch
        $parts = explode('.', $value);
        while (count($parts) < 3) {
            $parts[] = '0';
        }

        return implode('.', array_slice($parts, 0, 3));
    }

    /**
     * @param  array<string, string>  $normalizedValues  [provider => normalized_value]
     */
    private function numberExceedsThreshold(array $normalizedValues): bool
    {
        $nums = array_map('floatval', array_values($normalizedValues));
        $min = min($nums);
        $max = max($nums);

        if ($max == 0) {
            return $min != 0;
        }

        return (($max - $min) / $max) > self::NUMBER_CONFLICT_THRESHOLD;
    }

    /**
     * @param  array<string, string>  $normalizedValues
     * @return array{kind: string, provider: ?string}
     */
    private function findMinority(array $normalizedValues): array
    {
        // For date type, we need to compare with granularity-aware logic
        // Group by value
        $grouped = [];
        foreach ($normalizedValues as $provider => $value) {
            $grouped[$value][] = $provider;
        }

        $providerCount = count($normalizedValues);

        if ($providerCount === 2) {
            return ['kind' => '1v1', 'provider' => null];
        }

        // 3+ providers
        foreach ($grouped as $value => $providers) {
            if (count($providers) === 1 && $providerCount - 1 >= 2) {
                return ['kind' => '2v1', 'provider' => $providers[0]];
            }
        }

        return ['kind' => 'no-majority', 'provider' => null];
    }

    /**
     * Low-discriminability: open question with no {boolean, date, number, version} aligned claims.
     *
     * @param  array<int, array{type: string}>  $aligned
     */
    private function isLowDiscriminability(array $aligned): bool
    {
        $majorTypes = ['boolean', 'date', 'number', 'version'];
        foreach ($aligned as $claim) {
            if (in_array($claim['type'], $majorTypes, true)) {
                return false;
            }
        }

        return true;
    }
}
