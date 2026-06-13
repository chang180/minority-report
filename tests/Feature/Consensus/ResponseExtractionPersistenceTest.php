<?php

use App\Consensus\Classifier\FailSafeQuestionClassifier;
use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\Contracts\QuestionClassifier;
use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\DTO\ClassificationResult;
use App\Consensus\DTO\ProviderResponse;
use App\Consensus\Extractor\JsonResponseExtractor;
use App\Consensus\ResponseExtractionOrchestrator;
use App\Models\ProviderResponse as ProviderResponseModel;
use App\Models\VerificationRequest;
use App\Repositories\EloquentProviderResponseRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('extraction updates provider response persistence fields', function () {
    $request = VerificationRequest::create(['question' => 'Is Laravel a PHP framework?']);
    $repository = new EloquentProviderResponseRepository;

    $rawResponse = new ProviderResponse(
        provider: 'openai',
        model: 'gpt-test',
        providerStatus: 'success',
        extractionStatus: 'not_started',
        rawAnswer: json_encode([
            'direct_answer' => 'yes',
            'summary' => 'Laravel is a PHP framework.',
            'claims' => [
                [
                    'type' => 'boolean',
                    'canonical_key' => 'laravel php framework',
                    'subject' => 'Laravel',
                    'predicate' => 'is',
                    'value' => 'PHP framework',
                ],
            ],
            'citations' => [],
        ], JSON_THROW_ON_ERROR),
    );

    $repository->save($request->id, $rawResponse, 'provider prompt');

    $responses = (new ResponseExtractionOrchestrator(
        new JsonResponseExtractor,
        $repository,
    ))->extractAndPersist(
        $request->id,
        [$rawResponse],
        new ClassificationResult(
            type: 'B',
            answerShape: 'discrete',
            requiresGrounding: false,
            classifierConfidence: 'high',
        ),
    );

    $record = ProviderResponseModel::whereBelongsTo($request)->firstOrFail();

    expect($responses[0]->extractionStatus)->toBe('success')
        ->and($record->provider_status)->toBe('success')
        ->and($record->extraction_status)->toBe('success')
        ->and($record->extraction_prompt)->toContain('Provider: openai')
        ->and($record->extractor_model)->toBe('fixture-json-replay')
        ->and($record->normalized['direct_answer'])->toBe('yes')
        ->and($record->normalized['claims'][0]['canonical_key'])->toBe('laravel php framework');
});

test('extractor is called independently for each provider response', function () {
    $request = VerificationRequest::create(['question' => 'Is Laravel a PHP framework?']);
    $repository = new EloquentProviderResponseRepository;

    $responses = [
        new ProviderResponse(
            provider: 'openai',
            providerStatus: 'success',
            rawAnswer: '{"summary":"P1","claims":[]}',
        ),
        new ProviderResponse(
            provider: 'anthropic',
            providerStatus: 'success',
            rawAnswer: '{"summary":"P2","claims":[]}',
        ),
    ];

    foreach ($responses as $response) {
        $repository->save($request->id, $response, 'provider prompt');
    }

    $extractor = new class implements ResponseExtractor
    {
        /**
         * @var array<int, string>
         */
        public array $seenRawAnswers = [];

        public function extract(
            ProviderResponse $providerResponse,
            ClassificationResult $classification,
        ): ProviderResponse {
            $this->seenRawAnswers[] = $providerResponse->rawAnswer;

            return new ProviderResponse(
                provider: $providerResponse->provider,
                providerStatus: $providerResponse->providerStatus,
                extractionStatus: 'success',
                rawAnswer: $providerResponse->rawAnswer,
                normalized: [
                    'answer_shape' => $classification->answerShape,
                    'direct_answer' => 'yes',
                    'summary' => $providerResponse->provider,
                    'claims' => [],
                    'citations' => [],
                ],
                extractionPrompt: 'single-provider prompt '.$providerResponse->provider,
                extractorModel: 'fake-independent-extractor',
            );
        }
    };

    (new ResponseExtractionOrchestrator($extractor, $repository))->extractAndPersist(
        $request->id,
        $responses,
        new ClassificationResult(
            type: 'B',
            answerShape: 'discrete',
            requiresGrounding: false,
            classifierConfidence: 'high',
        ),
    );

    expect($extractor->seenRawAnswers)->toBe([
        '{"summary":"P1","claims":[]}',
        '{"summary":"P2","claims":[]}',
    ])
        ->and(ProviderResponseModel::whereBelongsTo($request)->where('extraction_status', 'success')->count())->toBe(2);
});

test('consensus service provider wires M4-A implementations', function () {
    expect(app(QuestionClassifier::class))->toBeInstanceOf(FailSafeQuestionClassifier::class)
        ->and(app(ResponseExtractor::class))->toBeInstanceOf(JsonResponseExtractor::class)
        ->and(app(ProviderResponseRepository::class))->toBeInstanceOf(EloquentProviderResponseRepository::class);
});
