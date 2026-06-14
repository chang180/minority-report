<?php

namespace App\Http\Middleware;

use App\Models\User;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AutoVerifyEmailLocally
{
    public function handle(Request $request, Closure $next): Response
    {
        if (! self::shouldAutoVerifyOnRequest()) {
            return $next($request);
        }

        $user = $request->user();

        if ($user instanceof User && ! $user->hasVerifiedEmail()) {
            $user->markEmailAsVerified();

            if ($request->routeIs('verification.notice')) {
                return redirect()->intended(route('dashboard'));
            }
        }

        return $next($request);
    }

    /** Local dev + explicit flag — not applied during PHPUnit (testing env). */
    public static function shouldAutoVerifyOnRequest(): bool
    {
        return app()->environment('local')
            || (bool) config('auth.auto_verify_email', false);
    }

    /** Registration / login events — includes testing for feature tests. */
    public static function shouldAutoVerifyOnAuthEvents(): bool
    {
        return app()->environment('local')
            || app()->environment('testing')
            || (bool) config('auth.auto_verify_email', false);
    }
}
