<?php

namespace App\AI\Providers;

use Laravel\Ai\Contracts\Agent;
use Laravel\Ai\Promptable;

class ConfiguredSynthesisAgent implements Agent
{
    use Promptable;

    public function instructions(): string
    {
        return implode("\n", [
            'You are the synthesizer seat of a multi-LLM consensus verification system.',
            'Write the final user-facing report in Traditional Chinese (繁體中文).',
            'Use clear sections such as: 最終答案、共識說明、少數意見（若有）、信任等級說明、已知限制。',
            'You MUST NOT change the supplied consensus status, trust level, or minority provider.',
            'You MUST NOT override deterministic consensus — only explain and organize witness answers.',
            'Synthesize witness summaries and claims into one readable answer; do not invent facts beyond the inputs.',
        ]);
    }
}
