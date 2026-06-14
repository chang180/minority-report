<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;

class VerificationRequestPolicy
{
    public function view(User $user, VerificationRequest $verificationRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $verificationRequest->user_id !== null
            && $verificationRequest->user_id === $user->id;
    }
}
