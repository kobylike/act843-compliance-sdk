<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Illuminate\Support\Facades\Auth;

class ComplianceEngine
{
    public static function analyze(string $type, int $score, array $meta = []): array
    {
        $severity = match (true) {
            $score >= 80 => 'HIGH',
            $score >= 50 => 'MEDIUM',
            default => 'LOW',
        };

        return [
            'type' => $type,
            'score' => $score,
            'severity' => $severity,
            'meta' => $meta,
        ];
    }

    public static function log(array $data): ComplianceLog
    {
        // Merge explanation into meta
        $meta = $data['meta'] ?? [];
        if (isset($data['explanation'])) {
            $meta['explanation'] = $data['explanation'];
        }

        return ComplianceLog::create([
            'type' => $data['type'],
            'ip_address' => request()->ip(),
            'score' => $data['score'],
            'severity' => $data['severity'],
            'attempts' => $data['attempts'] ?? 0,
            'meta' => $meta,
            'recommendation' => $data['recommendation'] ?? null,
        ]);
    }

    public static function unauthorizedAccess(string $route): void
    {
        self::log([
            'type' => 'UNAUTHORIZED_ACCESS',
            'score' => 80,
            'severity' => 'HIGH',
            'meta' => ['route' => $route],
        ]);
    }
}
