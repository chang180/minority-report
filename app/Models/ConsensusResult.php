<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable([
    'verification_request_id',
    'alignment',
    'conflict_detection',
    'consensus',
    'decision_key',
    'decision_basis',
    'trust_base',
    'applied_caps',
    'trust_level',
    'verdict_report',
    'errors',
    'metadata',
])]
class ConsensusResult extends Model
{
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
            'alignment' => 'array',
            'conflict_detection' => 'array',
            'consensus' => 'array',
            'decision_basis' => 'array',
            'applied_caps' => 'array',
            'verdict_report' => 'array',
            'errors' => 'array',
            'metadata' => 'array',
        ];
    }
}
