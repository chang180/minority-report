<?php

namespace App\Http\Controllers;

use App\AI\Providers\ConsensusSlotReadiness;
use App\Models\UserCustomProvider;
use App\Models\VerificationRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class DashboardController extends Controller
{
    private const PRESET_LABELS = [
        'openai' => 'OpenAI',
        'anthropic' => 'Anthropic',
        'gemini' => 'Google Gemini',
        'groq' => 'Groq',
        'ollama' => 'Ollama',
    ];

    public function __invoke(Request $request): Response
    {
        $user = $request->user();
        $slots = $user->consensus_slots ?? [];

        $slotStatuses = array_map(function (string $logicalName) use ($slots, $user): array {
            $slot = $slots[$logicalName] ?? null;

            if ($slot === null) {
                return ['slot' => $logicalName, 'type' => null, 'provider_label' => '未指定', 'ready' => false];
            }

            if ($slot['type'] === 'preset') {
                $key = $slot['provider_key'];

                return [
                    'slot' => $logicalName,
                    'type' => 'preset',
                    'provider_label' => self::PRESET_LABELS[$key] ?? $key,
                    'ready' => ConsensusSlotReadiness::isPresetReady($user, $key),
                ];
            }

            if ($slot['type'] === 'custom') {
                /** @var UserCustomProvider|null $custom */
                $custom = $user->customProviders()->find((int) $slot['custom_provider_id']);

                return [
                    'slot' => $logicalName,
                    'type' => 'custom',
                    'provider_label' => $custom ? $custom->label : '已刪除的自訂供應端',
                    'ready' => ConsensusSlotReadiness::isCustomReady($user, (int) $slot['custom_provider_id']),
                ];
            }

            return ['slot' => $logicalName, 'type' => null, 'provider_label' => '未指定', 'ready' => false];
        }, ['openai', 'anthropic', 'gemini']);

        $recentVerifications = VerificationRequest::where('user_id', $user->id)
            ->latest()
            ->limit(5)
            ->get()
            ->map(fn (VerificationRequest $v): array => [
                'id' => $v->id,
                'question' => $v->question,
                'final_trust' => $v->final_trust,
                'final_verdict' => $v->final_verdict,
                'created_at' => $v->created_at?->toDateTimeString(),
            ])
            ->values()
            ->all();

        $totalVerifications = VerificationRequest::where('user_id', $user->id)->count();

        return Inertia::render('Dashboard', [
            'slotStatuses' => $slotStatuses,
            'recentVerifications' => $recentVerifications,
            'totalVerifications' => $totalVerifications,
        ]);
    }
}
