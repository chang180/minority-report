<?php

namespace App\Providers;

use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\Consensus\Aligner\StringClaimAligner;
use App\Consensus\Analyzer\HybridConsensusAnalyzer;
use App\Consensus\Classifier\FailSafeQuestionClassifier;
use App\Consensus\Contracts\ClaimAligner;
use App\Consensus\Contracts\ConsensusAnalyzer;
use App\Consensus\Contracts\FakeProviderRegistry;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\Contracts\ProviderResponseRepository;
use App\Consensus\Contracts\QuestionClassifier;
use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\Contracts\TrustLevelScorer;
use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\Extractor\JsonResponseExtractor;
use App\Consensus\Fake\InMemoryFakeProviderRegistry;
use App\Consensus\Scorer\CascadeTrustLevelScorer;
use App\Consensus\Stubs\NullVerdictReporter;
use App\Repositories\EloquentProviderResponseRepository;
use Illuminate\Support\ServiceProvider;

class ConsensusServiceProvider extends ServiceProvider
{
    public $bindings = [
        QuestionClassifier::class => FailSafeQuestionClassifier::class,
        ResponseExtractor::class => JsonResponseExtractor::class,
        ClaimAligner::class => StringClaimAligner::class,
        ConsensusAnalyzer::class => HybridConsensusAnalyzer::class,
        TrustLevelScorer::class => CascadeTrustLevelScorer::class,
        VerdictReporter::class => NullVerdictReporter::class,
        ProviderResponseRepository::class => EloquentProviderResponseRepository::class,
    ];

    public $singletons = [
        FakeProviderRegistry::class => InMemoryFakeProviderRegistry::class,
    ];

    public function register(): void
    {
        $this->app->singleton(ConfiguredLlmProviderFactory::class);

        $this->app->bind(
            LlmProvider::class,
            fn ($app) => $app->make(ConfiguredLlmProviderFactory::class)->default(),
        );
    }

    public function boot(): void {}
}
