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
        $driver = config('compliance.rbac.driver', 'spatie');
        $roleColumn = config('compliance.rbac.role_column', 'role');

        $user = Auth::user();
        $hasRole = false;

        if ($user) {
            if ($driver === 'spatie') {
                // Spatie Laravel Permission
                $hasRole = method_exists($user, 'hasRole') && $user->hasRole($requiredRole);
            } elseif ($driver === 'native') {
                // Native role column
                $hasRole = ($user->{$roleColumn} ?? null) === $requiredRole;
            }
        }

        if (!$hasRole) {
            // Log unauthorised attempt
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
                    'driver' => $driver,
                    'explanation' => "User without '{$requiredRole}' role attempted to access {$request->path()} (driver: {$driver})",
                ],
                'recommendation' => 'Review access logs – possible privilege escalation attempt.',
            ]);

            if (!$logOnly) {
                abort(403, 'Unauthorized – ' . ucfirst($requiredRole) . ' officer access only.');
            }
        }

        return $next($request);
    }
}
