<?php

return [
    'number_conflict_relative_threshold' => 0.05,
    'providers' => [
        'openai' => [
            'enabled' => filled(env('OPENAI_API_KEY')) || filled(env('LOCAL_AI_API_URL')),
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
        'request_seconds' => (int) env('CONSENSUS_REQUEST_TIMEOUT_SECONDS', 300),
        'job_seconds' => (int) env('CONSENSUS_JOB_TIMEOUT_SECONDS', 330),
        'provider_seconds' => (int) env('CONSENSUS_PROVIDER_TIMEOUT_SECONDS', 90),
        'extractor_seconds' => (int) env('CONSENSUS_EXTRACTOR_TIMEOUT_SECONDS', 30),
    ],
];
