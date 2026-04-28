<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\AttackTransition;
use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Illuminate\Support\Facades\Cache;

class AttackGraphPredictor
{
    /**
     * Record an attack transition based on sequential logs from same IP.
     */
    public function recordTransition(string $ip, string $currentType, string $currentRoute)
    {
        $lastAttack = Cache::get("last_attack_{$ip}");
        if ($lastAttack) {
            AttackTransition::recordTransition(
                $lastAttack['type'],
                $lastAttack['route'],
                $currentType,
                $currentRoute
            );
        }
        Cache::put("last_attack_{$ip}", ['type' => $currentType, 'route' => $currentRoute], now()->addMinutes(30));
    }

    /**
     * Predict the next probable attack given current context.
     */
    public function predict(string $currentType, string $currentRoute): array
    {
        $transitions = AttackTransition::where('from_type', $currentType)
            ->where('from_route', $currentRoute)
            ->orderByDesc('probability')
            ->take(5)
            ->get(['to_type', 'to_route', 'probability']);

        return $transitions->map(fn($t) => [
            'type' => $t->to_type,
            'route' => $t->to_route,
            'probability' => round($t->probability * 100, 2),
        ])->toArray();
    }

    /**
     * Get overall attack path statistics for dashboard.
     */
    public function getHotPaths(): array
    {
        return AttackTransition::orderByDesc('weight')
            ->take(10)
            ->get(['from_type', 'from_route', 'to_type', 'to_route', 'weight'])
            ->toArray();
    }
}
