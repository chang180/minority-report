<?php

use App\Models\User;
use App\Models\UserCustomProvider;
use App\Models\UserProviderSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('unauthenticated user is redirected from provider settings', function () {
    $this->get('/settings/providers')->assertRedirect('/login');
});

test('authenticated user can view provider settings page', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->get('/settings/providers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('settings/Providers')
            ->has('presets')
            ->has('customProviders')
            ->has('consensusSlots')
        );
});

test('user can save a preset provider key', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/providers/preset', [
            'provider_key' => 'openai',
            'api_key' => 'sk-test-1234',
            'api_url' => null,
            'model' => 'gpt-4o',
            'provider_options_json' => '',
            'enabled' => true,
        ])
        ->assertRedirect();

    $setting = UserProviderSettings::where('user_id', $user->id)
        ->where('provider_key', 'openai')
        ->first();

    expect($setting)->not->toBeNull()
        ->and($setting->api_key)->toBe('sk-test-1234')
        ->and($setting->model)->toBe('gpt-4o')
        ->and($setting->enabled)->toBeTrue();
});

test('preset api_key is stored encrypted', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->put('/settings/providers/preset', [
        'provider_key' => 'anthropic',
        'api_key' => 'sk-ant-secret',
        'api_url' => null,
        'model' => null,
        'provider_options_json' => '',
        'enabled' => true,
    ]);

    $raw = DB::table('user_provider_settings')
        ->where('user_id', $user->id)
        ->where('provider_key', 'anthropic')
        ->value('api_key');

    expect($raw)->not->toBe('sk-ant-secret'); // must be encrypted
});

test('preset api_key is NOT returned in Inertia props', function () {
    $user = User::factory()->create();
    UserProviderSettings::create([
        'user_id' => $user->id,
        'provider_key' => 'openai',
        'api_key' => 'sk-super-secret',
        'enabled' => true,
    ]);

    $response = $this->actingAs($user)->get('/settings/providers');
    $response->assertInertia(fn (Assert $page) => $page
        ->component('settings/Providers')
        ->where('presets.0.has_key', true)
    );

    $content = $response->getContent();
    expect($content)->not->toContain('sk-super-secret');
});

test('empty api_key in preset update does not wipe stored key', function () {
    $user = User::factory()->create();
    UserProviderSettings::create([
        'user_id' => $user->id,
        'provider_key' => 'openai',
        'api_key' => 'existing-key',
        'enabled' => true,
    ]);

    $this->actingAs($user)->put('/settings/providers/preset', [
        'provider_key' => 'openai',
        'api_key' => '',
        'api_url' => null,
        'model' => 'gpt-4o-mini',
        'provider_options_json' => '',
        'enabled' => false,
    ]);

    $setting = UserProviderSettings::where('user_id', $user->id)
        ->where('provider_key', 'openai')
        ->first();

    expect($setting->api_key)->toBe('existing-key')
        ->and($setting->model)->toBe('gpt-4o-mini')
        ->and($setting->enabled)->toBeFalse();
});

test('user can add a custom provider', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->post('/settings/providers/custom', [
            'label' => 'My Ollama',
            'api_url' => 'http://localhost:11434/v1',
            'api_key' => '',
            'model' => 'llama3',
            'provider_options_json' => '{"max_tokens": 4096}',
            'enabled' => true,
        ])
        ->assertRedirect();

    expect($user->customProviders()->count())->toBe(1)
        ->and($user->customProviders()->first()->label)->toBe('My Ollama')
        ->and($user->customProviders()->first()->provider_options)->toMatchArray(['max_tokens' => 4096]);
});

test('user can delete their own custom provider', function () {
    $user = User::factory()->create();
    $provider = UserCustomProvider::create([
        'user_id' => $user->id,
        'label' => 'Test',
        'api_url' => 'http://localhost/v1',
        'enabled' => true,
    ]);

    $this->actingAs($user)
        ->delete("/settings/providers/custom/{$provider->id}")
        ->assertRedirect();

    expect(UserCustomProvider::find($provider->id))->toBeNull();
});

test('user cannot delete another user\'s custom provider', function () {
    $owner = User::factory()->create();
    $attacker = User::factory()->create();
    $provider = UserCustomProvider::create([
        'user_id' => $owner->id,
        'label' => 'Protected',
        'api_url' => 'http://localhost/v1',
        'enabled' => true,
    ]);

    $this->actingAs($attacker)
        ->delete("/settings/providers/custom/{$provider->id}")
        ->assertForbidden();

    expect(UserCustomProvider::find($provider->id))->not->toBeNull();
});

test('user can save consensus slots', function () {
    $user = User::factory()->create();

    $slots = [
        'openai' => ['type' => 'preset', 'provider_key' => 'openai'],
        'anthropic' => ['type' => 'preset', 'provider_key' => 'anthropic'],
        'gemini' => ['type' => 'preset', 'provider_key' => 'gemini'],
        'synthesis_enabled' => false,
        'synthesizer_slot' => 'anthropic',
    ];

    $this->actingAs($user)
        ->put('/settings/providers/slots', ['consensus_slots' => $slots])
        ->assertRedirect();

    expect($user->fresh()->consensus_slots)->toMatchArray($slots);
});

test('user can save preset provider options json', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/providers/preset', [
            'provider_key' => 'ollama',
            'api_key' => '',
            'api_url' => 'http://127.0.0.1:8080/v1',
            'model' => 'gemma',
            'provider_options_json' => '{"max_tokens": 2048, "temperature": 0.2}',
            'enabled' => true,
        ])
        ->assertRedirect();

    $setting = UserProviderSettings::where('user_id', $user->id)
        ->where('provider_key', 'ollama')
        ->first();

    expect($setting->provider_options)->toMatchArray([
        'max_tokens' => 2048,
        'temperature' => 0.2,
    ]);
});

test('invalid provider options json returns validation error', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/settings/providers/preset', [
            'provider_key' => 'openai',
            'api_key' => 'sk-test',
            'api_url' => null,
            'model' => null,
            'provider_options_json' => 'not-json',
            'enabled' => true,
        ])
        ->assertSessionHasErrors('provider_options_json');
});

test('provider settings page exposes provider options json', function () {
    $user = User::factory()->create();
    UserProviderSettings::create([
        'user_id' => $user->id,
        'provider_key' => 'openai',
        'api_key' => 'sk-test',
        'provider_options' => ['max_tokens' => 1024],
        'enabled' => true,
    ]);

    $this->actingAs($user)
        ->get('/settings/providers')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('presets.0.provider_options_json', fn (string $json) => str_contains($json, '1024'))
        );
});
