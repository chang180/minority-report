<?php

namespace App\Http\Controllers;

use App\Models\SystemAlignerSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminAlignerController extends Controller
{
    public function show(): Response
    {
        $settings = SystemAlignerSettings::instance();

        return Inertia::render('admin/AlignerSettings', [
            'settings' => [
                'mode' => $settings->mode,
                'enabled' => $settings->enabled,
                'local_api_url' => $settings->local_api_url,
                'local_model' => $settings->local_model,
                'has_local_api_key' => filled($settings->local_api_key),
                'timeout_seconds' => $settings->timeout_seconds,
                'min_confidence' => $settings->min_confidence,
            ],
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['string', 'semantic_llm'])],
            'enabled' => ['required', 'boolean'],
            'local_api_url' => ['nullable', 'url', 'max:512'],
            'local_model' => ['nullable', 'string', 'max:128'],
            'local_api_key' => ['nullable', 'string', 'max:512'],
            'timeout_seconds' => ['required', 'integer', 'min:5', 'max:120'],
            'min_confidence' => ['required', Rule::in(['high', 'medium'])],
        ]);

        $settings = SystemAlignerSettings::instance();

        $settings->mode = $validated['mode'];
        $settings->enabled = $validated['enabled'];
        $settings->local_api_url = $validated['local_api_url'] ?? null;
        $settings->local_model = $validated['local_model'] ?? null;
        $settings->timeout_seconds = $validated['timeout_seconds'];
        $settings->min_confidence = $validated['min_confidence'];

        if (filled($validated['local_api_key'])) {
            $settings->local_api_key = $validated['local_api_key'];
        }

        $settings->save();

        return back()->with('status', 'Aligner 設定已儲存。');
    }
}
