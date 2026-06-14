<?php

namespace App\Http\Controllers;

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\Consensus\ConsensusWorkflow;
use App\Consensus\DTO\Question;
use App\Models\ProviderResponse;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AuthVerificationController extends Controller
{
    public function __construct(
        private readonly ConsensusWorkflow $workflow,
        private readonly ConfiguredLlmProviderFactory $factory,
    ) {}

    public function create(): Response
    {
        return Inertia::render('Verification/Create');
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'question' => ['required', 'string', 'min:8', 'max:2000'],
        ]);

        $user = $request->user();
        $providers = $this->factory->forUser($user);

        $verification = $this->workflow->run(
            question: new Question(
                text: trim($validated['question']),
                metadata: ['source' => 'authenticated'],
            ),
            providers: $providers,
        );

        // Attach user and audit metadata without touching app/Consensus/.
        $verification->update([
            'user_id' => $user->id,
            'metadata' => array_merge($verification->metadata ?? [], [
                'source' => 'authenticated',
            ]),
        ]);

        return redirect()->route('verifications.show', $verification);
    }

    public function show(Request $request, VerificationRequest $verification): Response
    {
        $this->authorize('view', $verification);

        $verification->load([
            'providerResponses' => fn ($query) => $query->oldest('id'),
            'consensusResult',
        ]);

        return Inertia::render('Verification/Show', [
            'verification' => $this->verificationPayload($verification),
            'providerResponses' => $verification->providerResponses
                ->map(fn (ProviderResponse $response): array => $this->providerPayload($response))
                ->values()
                ->all(),
            'consensusResult' => $verification->consensusResult?->only([
                'alignment',
                'conflict_detection',
                'consensus',
                'decision_key',
                'decision_basis',
                'trust_base',
                'applied_caps',
                'trust_level',
                'verdict_report',
                'errors',
                'metadata',
            ]),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function verificationPayload(VerificationRequest $verification): array
    {
        return [
            'id' => $verification->id,
            'question' => $verification->question,
            'classified_type' => $verification->classified_type,
            'classifier_confidence' => $verification->classifier_confidence,
            'answer_shape' => $verification->answer_shape,
            'requires_grounding' => $verification->requires_grounding,
            'grounding_available' => $verification->grounding_available,
            'consensus_summary' => $verification->consensus_summary,
            'final_trust' => $verification->final_trust,
            'final_verdict' => $verification->final_verdict,
            'errors' => $verification->errors,
            'metadata' => $verification->metadata,
            'created_at' => $verification->created_at?->toISOString(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function providerPayload(ProviderResponse $response): array
    {
        return [
            'id' => $response->id,
            'provider' => $response->provider,
            'model' => $response->model,
            'provider_status' => $response->provider_status,
            'extraction_status' => $response->extraction_status,
            'raw_answer' => $response->raw_answer,
            'normalized' => $response->normalized,
            'claims' => $response->normalized['claims'] ?? [],
            'usage' => $response->usage,
            'error' => $response->error,
            'metadata' => $response->metadata,
        ];
    }
}
