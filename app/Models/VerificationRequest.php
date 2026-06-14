<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable([
    'user_id',
    'question',
    'processing_status',
    'classified_type',
    'classifier_confidence',
    'answer_shape',
    'requires_grounding',
    'grounding_available',
    'consensus_summary',
    'final_trust',
    'final_verdict',
    'errors',
    'metadata',
])]
class VerificationRequest extends Model
{
    protected $attributes = [
        'requires_grounding' => false,
        'grounding_available' => false,
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function providerResponses(): HasMany
    {
        return $this->hasMany(ProviderResponse::class);
    }

    public function consensusResult(): HasOne
    {
        return $this->hasOne(ConsensusResult::class);
    }

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'requires_grounding' => 'boolean',
            'grounding_available' => 'boolean',
            'consensus_summary' => 'array',
            'errors' => 'array',
            'metadata' => 'array',
        ];
    }
}
