<?php

use App\Alignment\ClaimAlignmentService;
use App\Alignment\Contracts\SemanticEquivalenceProvider;
use App\Consensus\Aligner\StringClaimAligner;
use App\Consensus\DTO\AlignmentResult;
use App\Consensus\DTO\ProviderResponse;
use App\Models\SystemAlignerSettings;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Inertia\Testing\AssertableInertia as Assert;

uses(RefreshDatabase::class);

// ── Admin CRUD ─────────────────────────────────────────────────────────────

test('unauthenticated user cannot access aligner settings', function () {
    $this->get('/admin/aligner')->assertRedirect('/login');
});

test('non-admin cannot access aligner settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)->get('/admin/aligner')->assertForbidden();
});

test('admin can view aligner settings page', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->get('/admin/aligner')
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('admin/AlignerSettings')
            ->has('settings')
            ->where('settings.mode', 'string')
        );
});

test('admin can update aligner mode and enabled flag', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)
        ->put('/admin/aligner', [
            'mode' => 'semantic_llm',
            'enabled' => true,
            'local_api_url' => 'http://localhost:8080',
            'local_model' => 'gemma3',
            'local_api_key' => '',
            'timeout_seconds' => 15,
            'min_confidence' => 'high',
        ])
        ->assertRedirect();

    $settings = SystemAlignerSettings::instance();
    expect($settings->mode)->toBe('semantic_llm')
        ->and($settings->enabled)->toBeTrue()
        ->and($settings->local_api_url)->toBe('http://localhost:8080')
        ->and($settings->local_model)->toBe('gemma3');
});

test('non-admin cannot update aligner settings', function () {
    $user = User::factory()->create();

    $this->actingAs($user)
        ->put('/admin/aligner', [
            'mode' => 'string',
            'enabled' => true,
            'timeout_seconds' => 15,
            'min_confidence' => 'high',
        ])
        ->assertForbidden();
});

test('aligner local api key is stored encrypted and not returned raw', function () {
    $admin = User::factory()->create(['role' => 'admin']);

    $this->actingAs($admin)->put('/admin/aligner', [
        'mode' => 'semantic_llm',
        'enabled' => true,
        'local_api_url' => 'http://localhost:8080',
        'local_model' => 'gemma3',
        'local_api_key' => 'my-aligner-secret',
        'timeout_seconds' => 15,
        'min_confidence' => 'high',
    ]);

    $raw = DB::table('system_aligner_settings')->value('local_api_key');
    expect($raw)->not->toBe('my-aligner-secret');

    $settings = SystemAlignerSettings::instance();
    expect($settings->local_api_key)->toBe('my-aligner-secret');
});

test('settings page does not expose raw api key in Inertia props', function () {
    $admin = User::factory()->create(['role' => 'admin']);
    $settings = SystemAlignerSettings::instance();
    $settings->local_api_key = 'super-secret';
    $settings->save();

    $this->actingAs($admin)
        ->get('/admin/aligner')
        ->assertInertia(fn (Assert $page) => $page
            ->where('settings.has_local_api_key', true)
            ->missing('settings.local_api_key')
        );
});

// ── String mode behaviour ───────────────────────────────────────────────────

test('string mode: ClaimAlignmentService delegates directly to StringClaimAligner', function () {
    $settings = SystemAlignerSettings::instance();
    $settings->mode = 'string';
    $settings->enabled = true;
    $settings->save();

    $responses = [
        makeProviderResponse('openai', [makeRawClaim('date', 'release date', '2024-03-15')]),
        makeProviderResponse('anthropic', [makeRawClaim('date', 'release date', '2024-03-15')]),
        makeProviderResponse('gemini', [makeRawClaim('date', 'release date', '2024-03-15')]),
    ];

    $service = app(ClaimAlignmentService::class);
    $result = $service->align($responses);

    expect($result->metadata['aligner_mode'])->toBe('string')
        ->and($result->aligned)->toHaveCount(1)
        ->and($result->unmatched)->toBeEmpty();
});

test('disabled setting: ClaimAlignmentService falls back to string regardless of mode', function () {
    $settings = SystemAlignerSettings::instance();
    $settings->mode = 'semantic_llm';
    $settings->enabled = false;
    $settings->save();

    $responses = [
        makeProviderResponse('openai', [makeRawClaim('date', 'release date', '2024-03-15')]),
        makeProviderResponse('anthropic', [makeRawClaim('date', 'release date', '2024-03-15')]),
    ];

    $service = app(ClaimAlignmentService::class);
    $result = $service->align($responses);

    expect($result->metadata['aligner_mode'])->toBe('string');
});

// ── Semantic LLM mode with mocked provider ─────────────────────────────────

test('semantic_llm mode: mock provider merges F16 synonym keys into aligned group', function () {
    $settings = SystemAlignerSettings::instance();
    $settings->mode = 'semantic_llm';
    $settings->enabled = true;
    $settings->local_api_url = 'http://localhost:8080';
    $settings->local_model = 'gemma3';
    $settings->save();

    // F16 scenario: three providers, same date value but different canonical keys
    $responses = [
        makeProviderResponse('openai', [makeRawClaim('date', 'release date', '2024-03-15')]),
        makeProviderResponse('anthropic', [makeRawClaim('date', 'product launch date', '2024-03-15')]),
        makeProviderResponse('gemini', [makeRawClaim('date', 'official launch date', '2024-03-15')]),
    ];

    // Mock the SemanticEquivalenceProvider to return a high-confidence cluster
    $mockProvider = Mockery::mock(SemanticEquivalenceProvider::class);
    $mockProvider->shouldReceive('clusterKeys')->once()->andReturn([
        'clusters' => [
            [
                'keys' => ['release date', 'product launch date', 'official launch date'],
                'equivalent' => true,
                'confidence' => 'high',
            ],
        ],
        'status' => 'success',
    ]);

    $service = new ClaimAlignmentService(new StringClaimAligner);

    // Inject mock via closure override
    $result = invokeAlignWithMockedProvider($service, $responses, $mockProvider);

    expect($result->metadata['aligner_mode'])->toBe('semantic_llm')
        ->and($result->metadata['semantic_skipped'])->toBeFalse()
        ->and($result->aligned)->toHaveCount(1)
        ->and($result->aligned[0]['providers'])->toHaveCount(3)
        ->and($result->unmatched)->toBeEmpty();
});

test('semantic_llm mode: low confidence cluster is not merged', function () {
    $settings = SystemAlignerSettings::instance();
    $settings->mode = 'semantic_llm';
    $settings->enabled = true;
    $settings->local_api_url = 'http://localhost:8080';
    $settings->local_model = 'gemma3';
    $settings->min_confidence = 'high';
    $settings->save();

    $responses = [
        makeProviderResponse('openai', [makeRawClaim('date', 'release date', '2024-03-15')]),
        makeProviderResponse('anthropic', [makeRawClaim('date', 'product launch date', '2024-03-15')]),
    ];

    $mockProvider = Mockery::mock(SemanticEquivalenceProvider::class);
    $mockProvider->shouldReceive('clusterKeys')->once()->andReturn([
        'clusters' => [
            [
                'keys' => ['release date', 'product launch date'],
                'equivalent' => true,
                'confidence' => 'low', // below threshold
            ],
        ],
        'status' => 'success',
    ]);

    $service = new ClaimAlignmentService(new StringClaimAligner);
    $result = invokeAlignWithMockedProvider($service, $responses, $mockProvider);

    // Still unmatched because confidence is too low
    expect($result->aligned)->toBeEmpty()
        ->and($result->unmatched)->toHaveCount(2);
});

test('LLM failure: fallback to string result with fallback_reason in metadata', function () {
    $settings = SystemAlignerSettings::instance();
    $settings->mode = 'semantic_llm';
    $settings->enabled = true;
    $settings->local_api_url = 'http://localhost:8080';
    $settings->local_model = 'gemma3';
    $settings->save();

    $responses = [
        makeProviderResponse('openai', [makeRawClaim('date', 'release date', '2024-03-15')]),
        makeProviderResponse('anthropic', [makeRawClaim('date', 'product launch date', '2024-03-15')]),
    ];

    $mockProvider = Mockery::mock(SemanticEquivalenceProvider::class);
    $mockProvider->shouldReceive('clusterKeys')->once()->andThrow(new RuntimeException('LLM connection timeout'));

    $service = new ClaimAlignmentService(new StringClaimAligner);
    $result = invokeAlignWithMockedProvider($service, $responses, $mockProvider);

    expect($result->metadata['aligner_mode'])->toBe('string')
        ->and($result->metadata['fallback_reason'])->toBe('LLM connection timeout')
        ->and($result->unmatched)->toHaveCount(2); // still unmatched, not erroneously merged
});

test('semantic_llm mode: no eligible candidates skips provider call', function () {
    $settings = SystemAlignerSettings::instance();
    $settings->mode = 'semantic_llm';
    $settings->enabled = true;
    $settings->local_api_url = 'http://localhost:8080';
    $settings->local_model = 'gemma3';
    $settings->save();

    // Only one provider → no candidates (unmatched from single provider, different type skipped)
    $responses = [
        makeProviderResponse('openai', [makeRawClaim('entity', 'product name', 'WidgetPro')]),
    ];

    $mockProvider = Mockery::mock(SemanticEquivalenceProvider::class);
    $mockProvider->shouldNotReceive('clusterKeys');

    $service = new ClaimAlignmentService(new StringClaimAligner);
    $result = invokeAlignWithMockedProvider($service, $responses, $mockProvider);

    expect($result->metadata['semantic_skipped'])->toBeTrue();
});

// ── Helpers ────────────────────────────────────────────────────────────────

/**
 * Helper: build a ProviderResponse with extracted claims already normalized.
 *
 * @param  array<int, array<string, mixed>>  $claims
 */
function makeProviderResponse(string $provider, array $claims): ProviderResponse
{
    return new ProviderResponse(
        provider: $provider,
        model: 'test-model',
        providerStatus: 'success',
        extractionStatus: 'success',
        rawAnswer: '',
        normalized: [
            'answer_shape' => 'open',
            'direct_answer' => 'yes',
            'summary' => '',
            'claims' => $claims,
            'citations' => [],
        ],
    );
}

/**
 * Helper: build a raw claim array matching extractor normalized output.
 *
 * @return array<string, mixed>
 */
function makeRawClaim(string $type, string $canonicalKey, string $value, ?string $unit = null): array
{
    return [
        'type' => $type,
        'canonical_key' => $canonicalKey,
        'subject' => $canonicalKey,
        'predicate' => 'is',
        'value' => $value,
        'unit' => $unit,
        'source' => null,
    ];
}

/**
 * Helper: run ClaimAlignmentService->align() but inject a mock SemanticEquivalenceProvider
 * by temporarily overriding the resolveProvider logic via a test subclass.
 *
 * @param  ProviderResponse[]  $responses
 */
function invokeAlignWithMockedProvider(
    ClaimAlignmentService $service,
    array $responses,
    SemanticEquivalenceProvider $mockProvider,
): AlignmentResult {
    // Use reflection to access the private runSemanticPass and inject the mock provider
    $class = new ReflectionClass($service);

    $stringAligner = new StringClaimAligner;
    $base = $stringAligner->align($responses);

    $settings = SystemAlignerSettings::instance();

    if ($settings->mode !== 'semantic_llm' || ! $settings->enabled) {
        return new AlignmentResult(
            aligned: $base->aligned,
            unmatched: $base->unmatched,
            unalignable: $base->unalignable,
            metadata: ['aligner_mode' => 'string'],
        );
    }

    $method = $class->getMethod('runSemanticPass');
    $method->setAccessible(true);

    return $method->invoke($service, $base, $mockProvider, $settings->min_confidence ?? 'high');
}
