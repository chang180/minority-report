<?php

namespace App\Providers;

use App\AI\Providers\AiTextProviderFactory;
use App\AI\Providers\ConfiguredLlmProviderFactory;
use App\Consensus\ConsensusParallelRunner;
use App\Consensus\ProviderQueryService;
use App\Consensus\ProviderSlotExecutor;
use App\Consensus\VerificationWorkflowProgress;
use App\Alignment\ClaimAlignmentService;
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
use App\Consensus\Synthesis\VerdictSynthesisPromptBuilder;
use App\Consensus\Synthesis\VerdictSynthesizer;
use App\Consensus\Verdict\StructuredVerdictReporter;
use App\Consensus\Verdict\SynthesizingVerdictReporter;
use App\Repositories\EloquentProviderResponseRepository;
use Illuminate\Support\ServiceProvider;

class ConsensusServiceProvider extends ServiceProvider
{
    public $bindings = [
        QuestionClassifier::class => FailSafeQuestionClassifier::class,
        ResponseExtractor::class => JsonResponseExtractor::class,
        ClaimAligner::class => ClaimAlignmentService::class,
        ConsensusAnalyzer::class => HybridConsensusAnalyzer::class,
        TrustLevelScorer::class => CascadeTrustLevelScorer::class,
        VerdictReporter::class => SynthesizingVerdictReporter::class,
        ProviderResponseRepository::class => EloquentProviderResponseRepository::class,
    ];

    public $singletons = [
        FakeProviderRegistry::class => InMemoryFakeProviderRegistry::class,
    ];

    public function register(): void
    {
        $this->app->singleton(AiTextProviderFactory::class);
        $this->app->singleton(ConfiguredLlmProviderFactory::class);
        $this->app->singleton(ConsensusParallelRunner::class);
        $this->app->singleton(VerificationWorkflowProgress::class);
        $this->app->singleton(ProviderQueryService::class);
        $this->app->singleton(ProviderSlotExecutor::class);
        $this->app->singleton(VerdictSynthesisPromptBuilder::class);
        $this->app->singleton(VerdictSynthesizer::class);
        $this->app->singleton(StructuredVerdictReporter::class);
        $this->app->singleton(SynthesizingVerdictReporter::class);

        $this->app->bind(
            LlmProvider::class,
            fn ($app) => $app->make(ConfiguredLlmProviderFactory::class)->default(),
        );
    }

    public function boot(): void {}
}
