<?php

namespace App\Consensus;

use App\Models\VerificationRequest;

class VerificationWorkflowProgress
{
    public const PHASE_DISPATCHING = 'dispatching';

    public const PHASE_EXTRACTING = 'extracting';

    public const PHASE_SYNTHESIZING = 'synthesizing';

    public const PHASE_ANALYZING = 'analyzing';

    public function setPhase(int $verificationRequestId, string $phase): void
    {
        $verification = VerificationRequest::query()->find($verificationRequestId);

        if ($verification === null) {
            return;
        }

        $metadata = $verification->metadata ?? [];
        $metadata['workflow_phase'] = $phase;

        $verification->update(['metadata' => $metadata]);
    }

    public function clearPhase(int $verificationRequestId): void
    {
        $verification = VerificationRequest::query()->find($verificationRequestId);

        if ($verification === null) {
            return;
        }

        $metadata = $verification->metadata ?? [];
        unset($metadata['workflow_phase']);

        $verification->update(['metadata' => $metadata]);
    }
}
