<?php
// app/Compliance/Evaluation/AttackSimulator.php

namespace GhanaCompliance\Act843SDK\Compliance\Evaluation;

use GhanaCompliance\Act843SDK\Services\ComplianceService;
use GhanaCompliance\Act843SDK\Models\ComplianceLog;
use Carbon\Carbon;

class AttackSimulator
{
    protected array $results = [];

    public function simulateBruteForce(string $ip, int $maxAttempts = 20): array
    {
        $start = microtime(true);
        $logsBefore = ComplianceLog::count();

        for ($i = 1; $i <= $maxAttempts; $i++) {
            app(ComplianceService::class)->process([
                'ip' => $ip,
                'attempts' => $i,
                'type' => 'SIMULATED_BRUTE_FORCE',
            ]);
        }

        $duration = (microtime(true) - $start) * 1000; // ms
        $logsAfter = ComplianceLog::count();
        $logsCreated = $logsAfter - $logsBefore;

        // Clean up simulation logs (optional)
        ComplianceLog::where('type', 'SIMULATED_BRUTE_FORCE')->delete();

        return [
            'attack_type' => 'brute_force',
            'attempts' => $maxAttempts,
            'logs_created' => $logsCreated,
            'duration_ms' => round($duration, 2),
            'avg_latency_per_request_ms' => round($duration / $maxAttempts, 2),
        ];
    }

    public function simulateCredentialStuffing(string $ip, int $uniqueUsernames = 20): array
    {
        // Simulate by calling pattern detection with many different usernames
        // For privacy, we use hashed usernames.
        $cacheKey = "usernames_{$ip}";
        $usernames = [];
        for ($i = 0; $i < $uniqueUsernames; $i++) {
            $usernames[] = 'user' . $i . '@example.com';
        }
        cache([$cacheKey => $usernames], 10);

        $start = microtime(true);
        app(ComplianceService::class)->process([
            'ip' => $ip,
            'attempts' => $uniqueUsernames,
            'type' => 'SIMULATED_CREDENTIAL_STUFFING',
        ]);
        $duration = (microtime(true) - $start) * 1000;

        cache()->forget($cacheKey);
        ComplianceLog::where('type', 'SIMULATED_CREDENTIAL_STUFFING')->delete();

        return [
            'attack_type' => 'credential_stuffing',
            'unique_usernames' => $uniqueUsernames,
            'duration_ms' => round($duration, 2),
        ];
    }
}
