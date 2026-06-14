<?php

namespace App\Http\Controllers;

use App\AI\Providers\ConsensusSlotReadiness;
use App\AI\Providers\ProviderGenerationOptions;
use App\Consensus\Synthesis\ConsensusSynthesisSettings;
use App\Models\UserCustomProvider;
use App\Rules\ProviderOptionsJson;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ProviderSettingsController extends Controller
{
    private const PRESET_CATALOG = [
        ['key' => 'openai', 'label' => 'OpenAI', 'cloud' => true],
        ['key' => 'anthropic', 'label' => 'Anthropic', 'cloud' => true],
        ['key' => 'gemini', 'label' => 'Google Gemini', 'cloud' => true],
        ['key' => 'groq', 'label' => 'Groq', 'cloud' => true],
        ['key' => 'ollama', 'label' => 'Ollama（本機）', 'cloud' => false],
    ];

    public function show(Request $request): Response
    {
        $user = $request->user();

        $presets = collect(self::PRESET_CATALOG)->map(function (array $preset) use ($user): array {
            $setting = $user->providerSettings()->where('provider_key', $preset['key'])->first();

            return [
                'provider_key' => $preset['key'],
                'label' => $preset['label'],
                'cloud' => $preset['cloud'],
                'has_key' => $setting && filled($setting->api_key),
                'configured' => ConsensusSlotReadiness::isPresetReady($user, $preset['key']),
                'api_url' => $setting?->api_url,
                'model' => $setting?->model,
                'provider_options_json' => ProviderGenerationOptions::toJson($setting?->provider_options),
                'enabled' => $setting?->enabled ?? true,
            ];
        })->values()->all();

        $customProviders = $user->customProviders()
            ->get()
            ->map(fn (UserCustomProvider $p): array => [
                'id' => $p->id,
                'label' => $p->label,
                'api_url' => $p->api_url,
                'model' => $p->model,
                'provider_options_json' => ProviderGenerationOptions::toJson($p->provider_options),
                'has_key' => filled($p->api_key),
                'configured' => ConsensusSlotReadiness::isCustomReady($user, $p->id),
                'enabled' => $p->enabled,
            ])
            ->values()
            ->all();

        return Inertia::render('settings/Providers', [
            'presets' => $presets,
            'customProviders' => $customProviders,
            'consensusSlots' => $user->consensus_slots ?? [],
            'synthesisSettings' => ConsensusSynthesisSettings::resolve($user->consensus_slots)->toArray(),
        ]);
    }

    public function updatePreset(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'provider_key' => ['required', 'string', 'max:64'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'api_url' => ['nullable', 'url', 'max:512'],
            'model' => ['nullable', 'string', 'max:128'],
            'provider_options_json' => ['nullable', 'string', 'max:4096', new ProviderOptionsJson],
            'enabled' => ['required', 'boolean'],
        ]);

        $user = $request->user();
        $setting = $user->providerSettings()->firstOrNew(['provider_key' => $validated['provider_key']]);
        $setting->user_id = $user->id;
        $setting->enabled = $validated['enabled'];
        $setting->api_url = $validated['api_url'] ?? null;
        $setting->model = $validated['model'] ?? null;
        $setting->provider_options = ProviderGenerationOptions::fromRequest($validated['provider_options_json'] ?? null);

        if (filled($validated['api_key'])) {
            $setting->api_key = $validated['api_key'];
        }

        $setting->save();

        return back()->with('status', '供應端設定已儲存。');
    }

    public function storeCustom(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'label' => ['required', 'string', 'max:128'],
            'api_url' => ['required', 'url', 'max:512'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'model' => ['nullable', 'string', 'max:128'],
            'provider_options_json' => ['nullable', 'string', 'max:4096', new ProviderOptionsJson],
            'enabled' => ['required', 'boolean'],
        ]);

        $request->user()->customProviders()->create([
            'label' => $validated['label'],
            'api_url' => $validated['api_url'],
            'api_key' => $validated['api_key'] ?? null,
            'model' => $validated['model'] ?? null,
            'enabled' => $validated['enabled'],
            'provider_options' => ProviderGenerationOptions::fromRequest($validated['provider_options_json'] ?? null),
        ]);

        return back()->with('status', '自訂供應端已新增。');
    }

    public function updateCustom(Request $request, UserCustomProvider $customProvider): RedirectResponse
    {
        abort_unless($customProvider->user_id === $request->user()->id, 403);

        $validated = $request->validate([
            'label' => ['required', 'string', 'max:128'],
            'api_url' => ['required', 'url', 'max:512'],
            'api_key' => ['nullable', 'string', 'max:512'],
            'model' => ['nullable', 'string', 'max:128'],
            'provider_options_json' => ['nullable', 'string', 'max:4096', new ProviderOptionsJson],
            'enabled' => ['required', 'boolean'],
        ]);

        $payload = [
            'label' => $validated['label'],
            'api_url' => $validated['api_url'],
            'model' => $validated['model'] ?? null,
            'enabled' => $validated['enabled'],
            'provider_options' => ProviderGenerationOptions::fromRequest($validated['provider_options_json'] ?? null),
        ];

        if (filled($validated['api_key'])) {
            $payload['api_key'] = $validated['api_key'];
        }

        $customProvider->update($payload);

        return back()->with('status', '自訂供應端已更新。');
    }

    public function destroyCustom(UserCustomProvider $customProvider, Request $request): RedirectResponse
    {
        abort_unless($customProvider->user_id === $request->user()->id, 403);
        $customProvider->delete();

        return back()->with('status', '自訂供應端已刪除。');
    }

    public function updateSlots(Request $request): RedirectResponse
    {
        $request->validate([
            'consensus_slots' => ['required', 'array'],
            'consensus_slots.synthesis_enabled' => ['sometimes', 'boolean'],
            'consensus_slots.synthesizer_slot' => ['sometimes', 'string', Rule::in(['openai', 'anthropic', 'gemini'])],
        ]);

        $consensusSlots = $request->input('consensus_slots', []);

        foreach (['openai', 'anthropic', 'gemini'] as $slot) {
            $slotDef = $consensusSlots[$slot] ?? null;

            if ($slotDef === null) {
                continue;
            }

            validator($slotDef, [
                'type' => ['required', Rule::in(['preset', 'custom'])],
                'provider_key' => ['sometimes', 'nullable', 'string'],
                'custom_provider_id' => ['sometimes', 'nullable', 'integer'],
            ])->validate();
        }

        $request->user()->update(['consensus_slots' => $consensusSlots]);

        return back()->with('status', '共識槽設定已儲存。');
    }
}
