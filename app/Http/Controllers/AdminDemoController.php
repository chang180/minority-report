<?php

namespace App\Http\Controllers;

use App\Consensus\Demo\ConsensusDemoFixtureCatalog;
use App\Models\SystemDemoSettings;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class AdminDemoController extends Controller
{
    public function __construct(
        private readonly ConsensusDemoFixtureCatalog $fixtures,
    ) {}

    public function show(): Response
    {
        $settings = SystemDemoSettings::instance();

        return Inertia::render('admin/DemoSettings', [
            'settings' => [
                'mode' => $settings->mode,
                'demo_enabled' => $settings->demo_enabled,
                'shared_api_url' => $settings->shared_api_url,
                'has_shared_api_key' => filled($settings->shared_api_key),
                'default_fixture_id' => $settings->default_fixture_id,
                'enabled_fixture_ids' => $settings->enabled_fixture_ids ?? $this->fixtures->ids(),
            ],
            'allFixtures' => $this->fixtures->options(),
        ]);
    }

    public function update(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'mode' => ['required', Rule::in(['fake_fixtures', 'shared_local_api'])],
            'demo_enabled' => ['required', 'boolean'],
            'shared_api_url' => ['nullable', 'url', 'max:512'],
            'shared_api_key' => ['nullable', 'string', 'max:512'],
            'default_fixture_id' => ['required', 'string', Rule::in($this->fixtures->ids())],
            'enabled_fixture_ids' => ['required', 'array'],
            'enabled_fixture_ids.*' => ['string', Rule::in($this->fixtures->ids())],
        ]);

        $settings = SystemDemoSettings::instance();

        $settings->mode = $validated['mode'];
        $settings->demo_enabled = $validated['demo_enabled'];
        $settings->shared_api_url = $validated['shared_api_url'] ?? null;
        $settings->default_fixture_id = $validated['default_fixture_id'];
        $settings->enabled_fixture_ids = $validated['enabled_fixture_ids'];

        if (filled($validated['shared_api_key'])) {
            $settings->shared_api_key = $validated['shared_api_key'];
        }

        $settings->save();

        return back()->with('status', 'Demo 設定已儲存。');
    }
}
