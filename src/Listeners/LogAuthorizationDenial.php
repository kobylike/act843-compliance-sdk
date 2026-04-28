<?php

namespace App\Listeners;

use Illuminate\Auth\Access\Events\GateEvaluated;
use Illuminate\Support\Facades\Cache;
use App\Services\Security\ComplianceEngine;

class LogAuthorizationDenial
{
    public function handle(GateEvaluated $event)
    {
        // Only log denials
        if ($event->result !== false) {
            return;
        }

        $user = $event->user;
        // Use getAuthIdentifier() – works for any Authenticatable user (Laravel default or custom)
        $userId = $user ? ($user->getAuthIdentifier() ?? $user->email ?? 'unknown') : 'guest';
        $ability = $event->ability;
        $arguments = $event->arguments;
        $resource = is_object($arguments[0] ?? null) ? get_class($arguments[0]) : ($arguments[0] ?? 'unknown');
        $ip = request()->ip();

        // Track repeated denials
        $cacheKey = "priv_esc_{$userId}_{$ability}_{$resource}";
        $attempts = Cache::get($cacheKey, 0) + 1;
        Cache::put($cacheKey, $attempts, now()->addMinutes(15));

        $baseScore = 70;
        $additional = ($attempts - 1) * 5;
        $score = min(100, $baseScore + $additional);
        $severity = $score >= 80 ? 'HIGH' : 'MEDIUM';

        ComplianceEngine::log([
            'type' => 'PRIVILEGE_ESCALATION',
            'score' => $score,
            'severity' => $severity,
            'attempts' => $attempts,
            'meta' => [
                'user_id' => $userId,
                'ability' => $ability,
                'resource' => $resource,
                'ip' => $ip,
                'user_agent' => request()->userAgent(),
            ],
            'recommendation' => $attempts > 3
                ? 'Immediate review – persistent privilege escalation attempts.'
                : 'Review user permissions; possible privilege escalation attempt.',
        ]);
    }
}
