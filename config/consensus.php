<?php

return [
    'number_conflict_relative_threshold' => 0.05,
    'providers' => [
        'openai' => ['enabled' => env('OPENAI_API_KEY') !== null],
        'anthropic' => ['enabled' => env('ANTHROPIC_API_KEY') !== null],
        'gemini' => ['enabled' => env('GEMINI_API_KEY') !== null],
    ],
    'timeouts' => [
        'provider_seconds' => 60,
        'extractor_seconds' => 30,
    ],
];
