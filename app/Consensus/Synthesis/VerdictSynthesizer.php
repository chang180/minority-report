<?php

namespace App\Consensus\Synthesis;

use App\AI\Providers\Contracts\ConnectionConfiguredLlmProvider;
use App\AI\Providers\SynthesisLlmProvider;
use App\AI\Providers\AiTextProviderFactory;
use App\Consensus\DTO\VerdictInput;

class VerdictSynthesizer
{
    public function __construct(
        private readonly VerdictSynthesisPromptBuilder $promptBuilder,
        private readonly AiTextProviderFactory $textProviderFactory,
    ) {}

    public function synthesize(string $questionText, VerdictInput $input, SynthesisRequest $request): ?string
    {
        $provider = $request->synthesizerProvider;

        if (! $provider instanceof ConnectionConfiguredLlmProvider) {
            return null;
        }

        $synthesisProvider = new SynthesisLlmProvider(
            logicalName: $provider->name(),
            connection: $provider->connectionConfig(),
            textProviderFactory: $this->textProviderFactory,
        );

        $prompt = $this->promptBuilder->build($questionText, $input);

        try {
            return $synthesisProvider->synthesize($prompt);
        } catch (\Throwable) {
            return null;
        }
    }
}
