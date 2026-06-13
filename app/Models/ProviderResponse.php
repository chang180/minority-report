<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'verification_request_id',
    'provider',
    'model',
    'provider_prompt',
    'provider_status',
    'extraction_prompt',
    'extractor_model',
    'extraction_status',
    'raw_answer',
    'normalized',
    'usage',
    'error',
    'metadata',
])]
class ProviderResponse extends Model
{
    protected $attributes = [
        'provider_status' => 'provider_unavailable',
        'extraction_status' => 'not_started',
    ];

    public function verificationRequest(): BelongsTo
    {
        return $this->belongsTo(VerificationRequest::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'normalized' => 'array',
            'usage' => 'array',
            'error' => 'array',
            'metadata' => 'array',
        ];
    }
}
