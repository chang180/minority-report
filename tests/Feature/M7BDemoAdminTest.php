<?php

use App\Models\SystemDemoSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

test('non-admin cannot access admin demo settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/demo')->assertForbidden();
});

test('unauthenticated user cannot access admin demo settings', function () {
    $this->get('/admin/demo')->assertRedirect('/login');
});

test('admin can view demo settings page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/demo')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/DemoSettings')
            ->has('settings')
            ->has('allFixtures')
        );
});

test('admin can update demo mode and demo_enabled', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/admin/demo', [
            'mode' => 'fake_fixtures',
            'demo_enabled' => false,
            'shared_api_url' => null,
            'shared_api_key' => '',
            'default_fixture_id' => 'M6-F02',
            'enabled_fixture_ids' => ['M6-F01', 'M6-F02'],
        ])
        ->assertRedirect();

    $settings = SystemDemoSettings::instance();
    expect($settings->demo_enabled)->toBeFalse()
        ->and($settings->mode)->toBe('fake_fixtures');
});

test('admin can set shared_local_api mode', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/admin/demo', [
            'mode' => 'shared_local_api',
            'demo_enabled' => true,
            'shared_api_url' => 'http://localhost:11434/v1',
            'shared_api_key' => 'my-local-key',
            'default_fixture_id' => 'M6-F02',
            'enabled_fixture_ids' => ['M6-F02'],
        ])
        ->assertRedirect();

    $settings = SystemDemoSettings::instance();
    expect($settings->mode)->toBe('shared_local_api')
        ->and($settings->shared_api_url)->toBe('http://localhost:11434/v1')
        ->and($settings->shared_api_key)->toBe('my-local-key');
});

test('demo shared_api_key is stored encrypted', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/admin/demo', [
        'mode' => 'shared_local_api',
        'demo_enabled' => true,
        'shared_api_url' => 'http://localhost:11434/v1',
        'shared_api_key' => 'super-secret-key',
        'default_fixture_id' => 'M6-F02',
        'enabled_fixture_ids' => ['M6-F02'],
    ]);

    $raw = DB::table('system_demo_settings')->value('shared_api_key');
    expect($raw)->not->toBe('super-secret-key');
});

test('demo returns closed page when demo_enabled is false', function () {
    SystemDemoSettings::create([
        'mode' => 'fake_fixtures',
        'demo_enabled' => false,
        'default_fixture_id' => 'M6-F02',
    ]);

    $this->get('/demo')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Demo/Closed'));
});

test('demo returns index when demo_enabled is true', function () {
    SystemDemoSettings::create([
        'mode' => 'fake_fixtures',
        'demo_enabled' => true,
        'default_fixture_id' => 'M6-F02',
    ]);

    $this->get('/demo')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page->component('Demo/Index'));
});

test('demo post is blocked when demo_enabled is false', function () {
    SystemDemoSettings::create([
        'mode' => 'fake_fixtures',
        'demo_enabled' => false,
        'default_fixture_id' => 'M6-F02',
    ]);

    $this->post('/demo/verifications', [
        'question' => 'Is the product launch date verified?',
        'fixture_id' => 'M6-F02',
    ])->assertStatus(404);
});

test('non-admin cannot update demo settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/admin/demo', [
            'mode' => 'fake_fixtures',
            'demo_enabled' => false,
            'shared_api_url' => null,
            'shared_api_key' => '',
            'default_fixture_id' => 'M6-F02',
            'enabled_fixture_ids' => ['M6-F02'],
        ])
        ->assertForbidden();
});
