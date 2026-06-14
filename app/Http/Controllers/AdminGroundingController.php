<?php

namespace App\Http\Controllers;

use App\Models\SystemGroundingSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminGroundingController extends Controller
{
    public function show(): Response
    {
        $settings = SystemGroundingSettings::instance();

        return Inertia::render('admin/GroundingSettings', [
            'settings' => [
                'mode' => $settings->mode,
                'enabled' => $settings->enabled,
                'local_api_url' => $settings->local_api_url,
                'local_model' => $settings->local_model,
                'has_local_api_key' => filled($settings->local_api_key),
                'search_provider' => $settings->search_provider,
                'has_search_api_key' => filled($settings->search_api_key),
                'search_api_url' => $settings->search_api_url,
                'max_tool_rounds' => $settings->max_tool_rounds,
                'timeout_seconds' => $settings->timeout_seconds,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['disabled', 'local_llm_tool_loop', 'search_api'])],
            'enabled' => ['required', 'boolean'],
            'local_api_url' => ['nullable', 'url', 'max:512'],
            'local_model' => ['nullable', 'string', 'max:128'],
            'local_api_key' => ['nullable', 'string', 'max:512'],
            'search_provider' => ['nullable', Rule::in(['tavily', 'serper', 'duckduckgo_lite'])],
            'search_api_key' => ['nullable', 'string', 'max:512'],
            'search_api_url' => ['nullable', 'url', 'max:512'],
            'max_tool_rounds' => ['required', 'integer', 'min:1', 'max:10'],
            'timeout_seconds' => ['required', 'integer', 'min:10', 'max:300'],
        ]);

        $settings = SystemGroundingSettings::instance();

        $settings->mode = $validated['mode'];
        $settings->enabled = $validated['enabled'];
        $settings->local_api_url = $validated['local_api_url'] ?? null;
        $settings->local_model = $validated['local_model'] ?? null;
        $settings->search_provider = $validated['search_provider'] ?? null;
        $settings->search_api_url = $validated['search_api_url'] ?? null;
        $settings->max_tool_rounds = $validated['max_tool_rounds'];
        $settings->timeout_seconds = $validated['timeout_seconds'];

        if (filled($validated['local_api_key'])) {
            $settings->local_api_key = $validated['local_api_key'];
        }

        if (filled($validated['search_api_key'])) {
            $settings->search_api_key = $validated['search_api_key'];
        }

        $settings->save();

        return back()->with('status', 'Grounding 設定已儲存。');
    }
}
