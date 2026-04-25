<?php

namespace GhanaCompliance\Act843SDK\Services;

use Illuminate\Support\Facades\Cache;
use GhanaCompliance\Act843SDK\Models\ComplianceLog;

class PatternDetectionService
{
    public function detect(array $data): array
    {
        $ip = $data['ip'];
        $attempts = $data['attempts'] ?? 0;
        $patterns = [];

        // Pattern 1: Rapid brute force (velocity)
        $velocity = $this->getRequestVelocity($ip);
        if ($velocity > 10) {
            $patterns[] = [
                'type' => 'AGGRESSIVE_BRUTE_FORCE',
                'score' => 85,
                'severity' => 'HIGH',
                'description' => "{$velocity} attempts per minute detected",
            ];
        } elseif ($velocity > 5) {
            $patterns[] = [
                'type' => 'BRUTE_FORCE',
                'score' => 70,
                'severity' => 'HIGH',
                'description' => "Rapid login attempts detected",
            ];
        }

        // Pattern 2: Off‑hours activity
        $hour = now()->hour;
        if (in_array($hour, [2, 3, 4, 5]) && $attempts >= 3) {
            $patterns[] = [
                'type' => 'OFF_HOURS_ATTACK',
                'score' => 40,
                'severity' => 'MEDIUM',
                'description' => 'Attack during unusual hours',
            ];
        }

        // Pattern 3: Distributed attack (same user agent from many IPs)
        $userAgent = request()->userAgent();
        if ($userAgent) {
            $similarAttacks = ComplianceLog::where('meta->user_agent', $userAgent)
                ->where('created_at', '>', now()->subHours(1))
                ->count();

            if ($similarAttacks > 10) {
                $patterns[] = [
                    'type' => 'DISTRIBUTED_ATTACK',
                    'score' => 75,
                    'severity' => 'HIGH',
                    'description' => "Same user agent from {$similarAttacks} different IPs",
                ];
            }
        }

        // Pattern 4: Credential stuffing (hashed usernames)
        $cacheKey = "usernames_{$ip}";
        $hashedUsernames = Cache::get($cacheKey, []);
        $uniqueCount = count($hashedUsernames);
        if ($uniqueCount > 10) {
            $patterns[] = [
                'type' => 'CREDENTIAL_STUFFING',
                'score' => min(100, 70 + $uniqueCount),
                'severity' => 'HIGH',
                'description' => "$uniqueCount distinct username hashes attempted from IP $ip",
            ];
        }

        return $patterns;
    }

    // Optional helper (already used above)
    public function detectCredentialStuffing(string $ip, int $uniqueCount): array
    {
        if ($uniqueCount > 10) {
            return [
                'type' => 'CREDENTIAL_STUFFING',
                'score' => min(100, 70 + $uniqueCount),
                'severity' => 'HIGH',
                'description' => "$uniqueCount distinct username hashes attempted from IP $ip",
            ];
        }
        return [];
    }

    protected function getRequestVelocity(string $ip): int
    {
        $key = "req_velocity_{$ip}";
        $timestamps = Cache::get($key, []);
        $now = now()->timestamp;

        $timestamps = array_filter($timestamps, fn($ts) => ($now - $ts) <= 60);
        $timestamps[] = $now;
        Cache::put($key, $timestamps, now()->addMinutes(1));

        return count($timestamps);
    }
}
