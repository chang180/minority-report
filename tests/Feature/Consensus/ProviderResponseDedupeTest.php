<?php

use App\Consensus\ProviderResponseCatalog;
use App\Models\ProviderResponse;
use App\Models\VerificationRequest;
use App\Repositories\EloquentProviderResponseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('provider response save upserts one row per verification slot', function () {
    $verification = VerificationRequest::create(['question' => 'Upsert test question']);
    $repository = app(EloquentProviderResponseRepository::class);

    $repository->save($verification->id, new \App\Consensus\DTO\ProviderResponse(
        provider: 'openai',
        providerStatus: 'success',
        extractionStatus: 'not_started',
        rawAnswer: 'first answer',
    ));

    $repository->save($verification->id, new \App\Consensus\DTO\ProviderResponse(
        provider: 'openai',
        providerStatus: 'success',
        extractionStatus: 'not_started',
        rawAnswer: 'second answer',
    ));

    expect(ProviderResponse::where('verification_request_id', $verification->id)->count())->toBe(1)
        ->and(ProviderResponse::where('verification_request_id', $verification->id)->value('raw_answer'))
        ->toBe('second answer');
});

test('provider response catalog returns latest row per slot in slot order', function () {
    $verification = VerificationRequest::create(['question' => 'Catalog test question']);

    ProviderResponse::create([
        'verification_request_id' => $verification->id,
        'provider' => 'gemini',
        'provider_status' => 'success',
        'extraction_status' => 'success',
        'raw_answer' => 'gemini answer',
    ]);

    ProviderResponse::create([
        'verification_request_id' => $verification->id,
        'provider' => 'openai',
        'provider_status' => 'success',
        'extraction_status' => 'success',
        'raw_answer' => 'openai answer',
    ]);

    ProviderResponse::create([
        'verification_request_id' => $verification->id,
        'provider' => 'anthropic',
        'provider_status' => 'success',
        'extraction_status' => 'success',
        'raw_answer' => 'anthropic answer',
    ]);

    $latest = ProviderResponseCatalog::latestForVerification($verification->id);

    expect($latest)->toHaveCount(3)
        ->and($latest->pluck('provider')->all())->toBe(['openai', 'anthropic', 'gemini']);
});
