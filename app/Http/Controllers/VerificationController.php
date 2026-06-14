<?php

namespace App\Http\Controllers;

use App\Consensus\ConsensusWorkflow;
use App\Consensus\Demo\ConsensusDemoFixtureCatalog;
use App\Consensus\DTO\Question;
use App\Grounding\GroundingService;
use App\Http\Requests\StoreVerificationRequest;
use App\Models\ProviderResponse;
use App\Models\SystemDemoSettings;
use App\Models\VerificationRequest;
use Illuminate\Http\RedirectResponse;
use Inertia\Inertia;
use Inertia\Response;

class VerificationController extends Controller
{
    public function __construct(
        private readonly ConsensusWorkflow $workflow,
        private readonly ConsensusDemoFixtureCatalog $fixtures,
        private readonly GroundingService $groundingService,
    ) {}

    public function index(): Response
    {
        $settings = SystemDemoSettings::instance();

        if (! $settings->demo_enabled) {
            return Inertia::render('Demo/Closed');
        }

        $enabledIds = $settings->enabled_fixture_ids ?? $this->fixtures->ids();
        $options = collect($this->fixtures->options())
            ->filter(fn (array $opt) => in_array($opt['id'], $enabledIds, true))
            ->values()
            ->all();

        return Inertia::render('Demo/Index', [
            'fixtures' => $options,
            'defaultFixtureId' => $settings->default_fixture_id,
        ]);
    }

    public function store(StoreVerificationRequest $request): RedirectResponse
    {
        $settings = SystemDemoSettings::instance();

        if (! $settings->demo_enabled) {
            abort(404);
        }

        $validated = $request->validated();
        $fixtureId = $validated['fixture_id'];
        $questionText = $validated['question'];
        $fixtureMetadata = $this->fixtures->metadataFor($fixtureId);

        $grounding = $this->groundingService->fetch($questionText, true);

        $groundingMetadata = [
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

        $verification = $this->workflow->run(
            question: new Question(
                text: $questionText,
                metadata: array_merge($fixtureMetadata, $groundingMetadata),
            ),
            providers: $this->fixtures->providersFor($fixtureId),
            providerPrompt: $providerPrompt,
        );

        $verification->update([
            'processing_status' => 'completed',
            'metadata' => array_merge($verification->metadata ?? [], [
                'source' => 'demo',
                'demo_mode' => $settings->mode,
            ]),
        ]);

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
            'processing_status' => $this->resolveProcessingStatus($verification),
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

    private function resolveProcessingStatus(VerificationRequest $verification): string
    {
        if ($verification->processing_status === 'completed' || $verification->processing_status === 'failed') {
            return $verification->processing_status;
        }

        // Demo runs synchronously; legacy rows may still be `pending` after M8-A migration.
        if (($verification->metadata['source'] ?? null) === 'demo' && $verification->consensusResult !== null) {
            return 'completed';
        }

        return $verification->processing_status ?? 'pending';
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
