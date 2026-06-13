<?php

return [
    'number_conflict_relative_threshold' => 0.05,
    'providers' => [
        'openai' => [
            'enabled' => filled(env('OPENAI_API_KEY')),
            'model' => env('OPENAI_MODEL'),
        ],
        'anthropic' => [
            'enabled' => filled(env('ANTHROPIC_API_KEY')),
            'model' => env('ANTHROPIC_MODEL'),
        ],
        'gemini' => [
            'enabled' => filled(env('GEMINI_API_KEY')),
            'model' => env('GEMINI_MODEL'),
        ],
    ],
    'timeouts' => [
        'provider_seconds' => 60,
        'extractor_seconds' => 30,
    ],
];
