<?php

namespace GhanaCompliance\Act843SDK\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use GhanaCompliance\Act843SDK\Services\Security\SecurityKernel;

class TrackSecurityEvents
{
    public function handle(Request $request, Closure $next)
    {
        SecurityKernel::track([
            'ip' => $request->ip(),
            'path' => $request->path(),
            'method' => $request->method(),
        ]);

        return $next($request);
    }
}
