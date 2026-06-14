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

    public int $timeout = 120;

    public function __construct(
        public readonly int $verificationRequestId,
        public readonly int $userId,
    ) {}

    public function handle(
        ConsensusWorkflow $workflow,
        ConfiguredLlmProviderFactory $factory,
        GroundingService $groundingService,
    ): void {
        $verification = VerificationRequest::findOrFail($this->verificationRequestId);
        $user = User::findOrFail($this->userId);

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
