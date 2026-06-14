<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('dashboard marks local ollama preset ready with api url only', function () {
    $user = User::factory()->create([
        'consensus_slots' => [
            'openai' => ['type' => 'preset', 'provider_key' => 'ollama'],
            'anthropic' => ['type' => 'preset', 'provider_key' => 'ollama'],
            'gemini' => ['type' => 'preset', 'provider_key' => 'ollama'],
        ],
    ]);

    $user->providerSettings()->create([
        'provider_key' => 'ollama',
        'api_url' => 'http://localhost:8080',
        'model' => 'gemma',
        'enabled' => true,
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard')
            ->where('slotStatuses.0.ready', true)
            ->where('slotStatuses.1.ready', true)
            ->where('slotStatuses.2.ready', true)
        );
});

test('dashboard marks custom local provider ready without api key', function () {
    $user = User::factory()->create();

    $custom = $user->customProviders()->create([
        'label' => 'Local LLM',
        'api_url' => 'http://localhost:8080/v1',
        'api_key' => null,
        'model' => 'gemma',
        'enabled' => true,
    ]);

    $user->update([
        'consensus_slots' => [
            'openai' => ['type' => 'custom', 'custom_provider_id' => $custom->id],
            'anthropic' => ['type' => 'custom', 'custom_provider_id' => $custom->id],
            'gemini' => ['type' => 'custom', 'custom_provider_id' => $custom->id],
        ],
    ]);

    $this->actingAs($user)
        ->get('/dashboard')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->where('slotStatuses.0.ready', true)
            ->where('slotStatuses.0.provider_label', 'Local LLM')
        );
});
