<?php

namespace App\Http\Controllers;

use App\Consensus\Replay\ConsensusReplayService;
use App\Jobs\RunAuthenticatedVerificationJob;
use App\Models\ProviderResponse;
use App\Models\VerificationRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class AuthVerificationController extends Controller
{
    public function __construct(
        private readonly ConsensusReplayService $replayService,
    ) {}

    public function index(Request $request): Response
    {
        $user = $request->user();
        $this->authorize('viewAny', VerificationRequest::class);

        $query = $user->isAdmin()
            ? VerificationRequest::latest()
            : VerificationRequest::where('user_id', $user->id)->latest();

        $verifications = $query->paginate(15)->through(fn (VerificationRequest $v): array => [
            'id' => $v->id,
            'question' => $v->question,
            'processing_status' => $v->processing_status,
            'final_trust' => $v->final_trust,
            'created_at' => $v->created_at?->toDateTimeString(),
        ]);

        return Inertia::render('Verification/Index', [
            'verifications' => $verifications,
        ]);
    }

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

        $verification = VerificationRequest::create([
            'user_id' => $user->id,
            'question' => trim($validated['question']),
            'processing_status' => 'pending',
            'metadata' => ['source' => 'authenticated'],
        ]);

        dispatch(new RunAuthenticatedVerificationJob($verification->id, $user->id));

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

    public function status(VerificationRequest $verification): JsonResponse
    {
        $this->authorize('view', $verification);

        $verification->load([
            'providerResponses' => fn ($query) => $query->oldest('id'),
        ]);

        return response()->json([
            'id' => $verification->id,
            'processing_status' => $verification->processing_status,
            'processing_error' => $verification->metadata['processing_error'] ?? null,
            'final_trust' => $verification->final_trust,
            'final_verdict' => $verification->final_verdict,
            'updated_at' => $verification->updated_at?->toISOString(),
            'provider_responses' => $verification->providerResponses
                ->map(fn (ProviderResponse $response): array => $this->providerPayload($response))
                ->values()
                ->all(),
        ]);
    }

    public function replay(Request $request, VerificationRequest $verification): RedirectResponse
    {
        $this->authorize('replay', $verification);

        $newVerification = $this->replayService->replayRequest($verification->id);

        $newVerification->update([
            'user_id' => $request->user()->id,
            'processing_status' => 'completed',
            'metadata' => array_merge($newVerification->metadata ?? [], [
                'source' => 'authenticated',
            ]),
        ]);

        return redirect()->route('verifications.show', $newVerification);
    }

    public function destroy(VerificationRequest $verification): RedirectResponse
    {
        $this->authorize('delete', $verification);

        $this->cancelQueuedJobsForVerification($verification->id);
        $verification->delete();

        return redirect()->route('verifications.index')->with('status', 'verification-deleted');
    }

    public function destroyAll(Request $request): RedirectResponse
    {
        $this->authorize('deleteAny', VerificationRequest::class);

        $user = $request->user();
        $query = $user->isAdmin()
            ? VerificationRequest::query()
            : VerificationRequest::where('user_id', $user->id);

        $ids = $query->pluck('id');

        foreach ($ids as $id) {
            $this->cancelQueuedJobsForVerification($id);
        }

        $query->delete();

        return redirect()->route('verifications.index')->with('status', 'verifications-cleared');
    }

    private function cancelQueuedJobsForVerification(int $verificationRequestId): void
    {
        DB::table('jobs')
            ->where('payload', 'like', '%RunAuthenticatedVerificationJob%')
            ->where('payload', 'like', '%verificationRequestId%')
            ->where('payload', 'like', '%i:'.$verificationRequestId.';%')
            ->delete();
    }

    /**
     * @return array<string, mixed>
     */
    private function verificationPayload(VerificationRequest $verification): array
    {
        return [
            'id' => $verification->id,
            'question' => $verification->question,
            'processing_status' => $verification->processing_status,
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
