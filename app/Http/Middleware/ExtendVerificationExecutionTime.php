<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ExtendVerificationExecutionTime
{
    public function handle(Request $request, Closure $next): Response
    {
        $seconds = (int) config('consensus.timeouts.request_seconds', 300);

        if ($seconds > 0) {
            set_time_limit($seconds);
        }

        return $next($request);
    }
}
