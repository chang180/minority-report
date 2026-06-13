<?php

test('health check returns ok status', function () {
    $this->getJson('/health')
        ->assertSuccessful()
        ->assertJson([
            'status' => 'ok',
            'app' => 'minority-report',
        ])
        ->assertJsonStructure(['status', 'app', 'laravel']);
});

test('health check laravel version matches app version', function () {
    $response = $this->getJson('/health')->assertSuccessful();

    expect($response->json('laravel'))->toBe(app()->version());
});
