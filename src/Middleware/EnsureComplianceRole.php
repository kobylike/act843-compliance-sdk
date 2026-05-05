<?php

namespace GhanaCompliance\Act843SDK\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use GhanaCompliance\Act843SDK\Services\Security\ComplianceEngine;

class EnsureComplianceRole
{
    public function handle(Request $request, Closure $next)
    {
        $requiredRole = config('compliance.rbac.required_role', 'compliance');
        $logOnly = config('compliance.rbac.log_only', false);

        $user = Auth::user();
        $hasRole = $user && method_exists($user, 'hasRole') && $user->hasRole($requiredRole);

        if (!$hasRole) {
            // Log every unauthorised attempt
            ComplianceEngine::log([
                'type' => 'UNAUTHORIZED_ACCESS',
                'score' => config('compliance.rbac.unauthorized_score', 80),
                'severity' => 'HIGH',
                'attempts' => 1,
                'meta' => [
                    'route' => $request->path(),
                    'ip' => $request->ip(),
                    'user_id' => $user?->id ?? 'guest',
                    'required_role' => $requiredRole,
                    'explanation' => "User without '{$requiredRole}' role attempted to access {$request->path()}",
                ],
                'recommendation' => 'Review access logs – possible privilege escalation attempt.',
            ]);

            // If log-only mode, let them through; otherwise block
            if (!$logOnly) {
                abort(403, 'Unauthorized – ' . ucfirst($requiredRole) . ' officer access only.');
            }
        }

        return $next($request);
    }
}
