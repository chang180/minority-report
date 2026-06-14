<?php

namespace App\Policies;

use App\Models\User;
use App\Models\VerificationRequest;

class VerificationRequestPolicy
{
    public function viewAny(User $user): bool
    {
        return true;
    }

    public function view(User $user, VerificationRequest $verificationRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $verificationRequest->user_id !== null
            && $verificationRequest->user_id === $user->id;
    }

    public function replay(User $user, VerificationRequest $verificationRequest): bool
    {
        if ($user->isAdmin()) {
            return true;
        }

        return $verificationRequest->user_id !== null
            && $verificationRequest->user_id === $user->id;
    }

    public function delete(User $user, VerificationRequest $verificationRequest): bool
    {
        return $this->view($user, $verificationRequest);
    }

    public function deleteAny(User $user): bool
    {
        return true;
    }
}
