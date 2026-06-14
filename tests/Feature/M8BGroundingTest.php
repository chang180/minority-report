<?php

use App\Grounding\DTO\GroundingResult;
use App\Grounding\DTO\GroundingSource;
use App\Grounding\GroundingService;
use App\Models\SystemGroundingSettings;
use App\Models\User;
use App\Models\VerificationRequest;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// ── Admin CRUD ─────────────────────────────────────────────────────────────

test('unauthenticated user cannot access grounding settings', function () {
    $this->get('/admin/grounding')->assertRedirect('/login');
});

test('non-admin cannot access grounding settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/grounding')->assertForbidden();
});

test('admin can view grounding settings page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/grounding')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/GroundingSettings')
            ->has('settings')
            ->where('settings.mode', 'disabled')
        );
});

test('admin can update grounding mode and enabled flag', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/admin/grounding', [
            'mode' => 'local_llm_tool_loop',
            'enabled' => true,
            'local_api_url' => 'http://localhost:8080',
            'local_model' => 'gemma3',
            'local_api_key' => '',
            'search_provider' => 'duckduckgo_lite',
            'search_api_key' => '',
            'search_api_url' => '',
            'max_tool_rounds' => 4,
            'timeout_seconds' => 120,
        ])
        ->assertRedirect();

    $settings = SystemGroundingSettings::instance();
    expect($settings->mode)->toBe('local_llm_tool_loop')
        ->and($settings->enabled)->toBeTrue()
        ->and($settings->local_api_url)->toBe('http://localhost:8080')
        ->and($settings->local_model)->toBe('gemma3');
});

test('non-admin cannot update grounding settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/admin/grounding', [
            'mode' => 'disabled',
            'enabled' => false,
            'max_tool_rounds' => 4,
            'timeout_seconds' => 120,
        ])
        ->assertForbidden();
});

test('grounding search api key is stored encrypted and not returned raw', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/admin/grounding', [
        'mode' => 'search_api',
        'enabled' => true,
        'local_api_url' => '',
        'local_model' => '',
        'local_api_key' => '',
        'search_provider' => 'tavily',
        'search_api_key' => 'super-secret-search-key',
        'search_api_url' => '',
        'max_tool_rounds' => 4,
        'timeout_seconds' => 120,
    ]);

    $raw = DB::table('system_grounding_settings')->value('search_api_key');
    expect($raw)->not->toBe('super-secret-search-key');

    $settings = SystemGroundingSettings::instance();
    expect($settings->search_api_key)->toBe('super-secret-search-key');
});

test('grounding local api key is stored encrypted', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/admin/grounding', [
        'mode' => 'local_llm_tool_loop',
        'enabled' => true,
        'local_api_url' => 'http://localhost:8080',
        'local_model' => 'gemma3',
        'local_api_key' => 'my-local-api-key',
        'search_provider' => 'duckduckgo_lite',
        'search_api_key' => '',
        'search_api_url' => '',
        'max_tool_rounds' => 4,
        'timeout_seconds' => 120,
    ]);

    $raw = DB::table('system_grounding_settings')->value('local_api_key');
    expect($raw)->not->toBe('my-local-api-key');
});

test('settings page does not expose raw api key in Inertia props', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $settings = SystemGroundingSettings::instance();
    $settings->search_api_key = 'secret-key';
    $settings->save();

    $this->actingAs($admin)
        ->get('/admin/grounding')
        ->assertInertia(fn (Assert $page) => $page
            ->where('settings.has_search_api_key', true)
            ->missing('settings.search_api_key')
        );
});

// ── Mode behaviour ─────────────────────────────────────────────────────────

test('disabled mode: GroundingService returns skipped with grounding_available=false', function () {
    $settings = SystemGroundingSettings::instance();
    $settings->enabled = false;
    $settings->mode = 'disabled';
    $settings->save();

    $service = app(GroundingService::class);
    $result = $service->fetch('Is PHP 9 released?', true);

    expect($result->groundingAvailable)->toBeFalse()
        ->and($result->status)->toBe('skipped');
});

test('GroundingService skips when requiresGrounding is false regardless of mode', function () {
    $settings = SystemGroundingSettings::instance();
    $settings->enabled = true;
    $settings->mode = 'search_api';
    $settings->save();

    $service = app(GroundingService::class);
    $result = $service->fetch('What is MVC?', false);

    expect($result->groundingAvailable)->toBeFalse()
        ->and($result->status)->toBe('skipped');
});

// ── Metadata injection via mocked provider ─────────────────────────────────

test('auth verification metadata contains grounding fields when provider succeeds', function () {
    $user = User::factory()->create();

    $mockSource = new GroundingSource('Example', 'https://example.com', 'Some snippet');
    $mockResult = new GroundingResult(
        status: 'success',
        groundingAvailable: true,
        query: 'test question',
        summary: 'Grounding summary here.',
        sources: [$mockSource],
        providerMode: 'search_api',
    );

    $this->mock(GroundingService::class, function ($mock) use ($mockResult) {
        $mock->shouldReceive('fetch')->andReturn($mockResult);
    });

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Is PHP 9 available right now?'])
        ->assertRedirect();

    $verification = VerificationRequest::latest('id')->first();

    expect($verification)->not->toBeNull()
        ->and($verification->metadata['grounding_available'])->toBeTrue()
        ->and($verification->metadata['grounding']['status'])->toBe('success')
        ->and($verification->metadata['grounding']['summary'])->toBe('Grounding summary here.');
});

test('grounding metadata does not contain api keys', function () {
    $user = User::factory()->create();

    $mockResult = GroundingResult::skipped('test question');

    $this->mock(GroundingService::class, function ($mock) use ($mockResult) {
        $mock->shouldReceive('fetch')->andReturn($mockResult);
    });

    $this->actingAs($user)
        ->post('/verifications', ['question' => 'Is PHP 9 available right now?'])
        ->assertRedirect();

    $verification = VerificationRequest::latest('id')->first();
    $groundingMeta = json_encode($verification->metadata['grounding'] ?? []);

    expect($groundingMeta)->not->toContain('api_key')
        ->and($groundingMeta)->not->toContain('secret');
});
