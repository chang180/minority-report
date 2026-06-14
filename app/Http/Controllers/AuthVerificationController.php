<?php

namespace App\Http\Controllers;

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\Consensus\ConsensusWorkflow;
use App\Consensus\DTO\Question;
use App\Grounding\GroundingService;
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
        private readonly GroundingService $groundingService,
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
        $questionText = trim($validated['question']);

        // Classify early to know requiresGrounding — we pass an empty metadata
        // so the workflow will classify; here we do a lightweight pre-check.
        // The workflow re-classifies internally, so we use a best-effort flag.
        // Per spec §6: grounding MUST run before ConsensusWorkflow::run().
        // We use a temporary Question to detect requiresGrounding via the grounding service.
        $preMetadata = ['source' => 'authenticated'];

        // Fetch grounding — GroundingService skips when requiresGrounding=false.
        // Since we don't have classification yet, we always attempt grounding;
        // the workflow's classifier will set requiresGrounding on the record.
        // For M8-B we conservatively attempt grounding and let the workflow persist
        // grounding_available from the metadata we pass.
        $grounding = $this->groundingService->fetch($questionText, true);

        $metadata = array_merge($preMetadata, [
            'grounding_available' => $grounding->groundingAvailable,
            'grounding' => $grounding->toMetadataArray(),
        ]);

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

        $verification = $this->workflow->run(
            question: new Question(
                text: $questionText,
                metadata: $metadata,
            ),
            providers: $providers,
            providerPrompt: $providerPrompt,
        );

        $verification->update([
            'user_id' => $user->id,
            'metadata' => array_merge($verification->metadata ?? [], [
                'source' => 'authenticated',
            ]),
        ]);

        return redirect()->route('verifications.show', $verification);
    }

    public function show(VerificationRequest $verification): Response
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
