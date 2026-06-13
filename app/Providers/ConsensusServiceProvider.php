<?php

namespace App\Providers;

use App\Consensus\Contracts\ClaimAligner;
use App\Consensus\Contracts\ConsensusAnalyzer;
use App\Consensus\Contracts\FakeProviderRegistry;
use App\Consensus\Contracts\LlmProvider;
use App\Consensus\Contracts\QuestionClassifier;
use App\Consensus\Contracts\ResponseExtractor;
use App\Consensus\Contracts\TrustLevelScorer;
use App\Consensus\Contracts\VerdictReporter;
use App\Consensus\Stubs\NullClaimAligner;
use App\Consensus\Stubs\NullConsensusAnalyzer;
use App\Consensus\Stubs\NullFakeProviderRegistry;
use App\Consensus\Stubs\NullLlmProvider;
use App\Consensus\Stubs\NullQuestionClassifier;
use App\Consensus\Stubs\NullResponseExtractor;
use App\Consensus\Stubs\NullTrustLevelScorer;
use App\Consensus\Stubs\NullVerdictReporter;
use Illuminate\Support\ServiceProvider;

class ConsensusServiceProvider extends ServiceProvider
{
    public $bindings = [
        QuestionClassifier::class => NullQuestionClassifier::class,
        LlmProvider::class => NullLlmProvider::class,
        ResponseExtractor::class => NullResponseExtractor::class,
        ClaimAligner::class => NullClaimAligner::class,
        ConsensusAnalyzer::class => NullConsensusAnalyzer::class,
        TrustLevelScorer::class => NullTrustLevelScorer::class,
        VerdictReporter::class => NullVerdictReporter::class,
        FakeProviderRegistry::class => NullFakeProviderRegistry::class,
    ];

    public function register(): void {}

    public function boot(): void {}
}
