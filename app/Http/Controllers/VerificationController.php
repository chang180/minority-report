<?php

namespace App\Http\Controllers;

use App\Consensus\ConsensusWorkflow;
use App\Consensus\Demo\ConsensusDemoFixtureCatalog;
use App\Consensus\DTO\Question;
use App\Http\Requests\StoreVerificationRequest;
use App\Models\ProviderResponse;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        private readonly ConsensusWorkflow $workflow,
        private readonly ConsensusDemoFixtureCatalog $fixtures,
    ) {}

    public function index(): Response
    {
        return Inertia::render('Verification/Index', [
            'fixtures' => $this->fixtures->options(),
            'defaultFixtureId' => $this->fixtures->defaultFixtureId(),
        ]);
    }

    public function store(StoreVerificationRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $fixtureId = $validated['fixture_id'];

        $verification = $this->workflow->run(
            question: new Question(
                text: $validated['question'],
                metadata: $this->fixtures->metadataFor($fixtureId),
            ),
            providers: $this->fixtures->providersFor($fixtureId),
        );

        return redirect()->route('demo.verifications.show', $verification);
    }

    public function show(VerificationRequest $verification): Response
    {
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
