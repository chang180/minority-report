<?php

namespace App\Jobs;

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\Consensus\ConsensusWorkflow;
use App\Consensus\DTO\Question;
use App\Grounding\GroundingService;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Throwable;

class RunAuthenticatedVerificationJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 2;

    public function __construct(
        public readonly int $verificationRequestId,
        public readonly int $userId,
    ) {
        $this->timeout = (int) config('consensus.timeouts.job_seconds', 330);
    }

    public function handle(
        ConsensusWorkflow $workflow,
        ConfiguredLlmProviderFactory $factory,
        GroundingService $groundingService,
    ): void {
        $seconds = (int) config('consensus.timeouts.request_seconds', 300);
        $previousLimit = ini_get('max_execution_time');
        if ($seconds > 0) {
            set_time_limit($seconds);
        }

        try {
            $this->runVerification($workflow, $factory, $groundingService, $seconds);
        } finally {
            if ($previousLimit !== false && $previousLimit !== '') {
                set_time_limit((int) $previousLimit);
            }
        }
    }

    private function runVerification(
        ConsensusWorkflow $workflow,
        ConfiguredLlmProviderFactory $factory,
        GroundingService $groundingService,
        int $seconds,
    ): void {
        if ($seconds > 0) {
            set_time_limit($seconds);
        }

        $verification = VerificationRequest::find($this->verificationRequestId);
        $user = User::findOrFail($this->userId);

        if ($verification === null) {
            return;
        }

        $verification->update(['processing_status' => 'running']);

        $providers = $factory->forUser($user);
        $questionText = trim($verification->question);

        $grounding = $groundingService->fetch($questionText, true);

        $metadata = [
            'source' => 'authenticated',
            'grounding_available' => $grounding->groundingAvailable,
            'grounding' => $grounding->toMetadataArray(),
        ];

        $providerPrompt = null;
        if ($grounding->groundingAvailable && $grounding->summary !== '') {
            $sourceLines = array_map(
                fn (array $s) => "- {$s['title']}: {$s['url']}",
                $grounding->toMetadataArray()['sources'],
            );
            $providerPrompt = implode("\n", [
                'External grounding summary (non-authoritative, for reference):',
                $grounding->summary,
                '',
                'Sources:',
                implode("\n", $sourceLines),
            ]);
        }

        $result = $workflow->run(
            question: new Question(
                text: $questionText,
                metadata: $metadata,
            ),
            providers: $providers,
            providerPrompt: $providerPrompt,
            existingRequest: $verification,
            consensusSlots: $user->consensus_slots,
        );

        $result->update([
            'user_id' => $user->id,
            'processing_status' => 'completed',
            'metadata' => array_merge($result->metadata ?? [], [
                'source' => 'authenticated',
            ]),
        ]);
    }

    public function failed(Throwable $exception): void
    {
        $verification = VerificationRequest::find($this->verificationRequestId);

        if ($verification !== null) {
            $verification->update([
                'processing_status' => 'failed',
                'metadata' => array_merge($verification->metadata ?? [], [
                    'processing_error' => $exception->getMessage(),
                ]),
            ]);
        }
    }
}
