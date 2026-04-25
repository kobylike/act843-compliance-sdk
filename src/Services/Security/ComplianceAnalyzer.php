<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\IpReputation;
use Illuminate\Support\Facades\Cache;
use GhanaCompliance\Act843SDK\Services\Security\ThreatIntelligenceService;
use GhanaCompliance\Act843SDK\Services\Security\GeoLocationService;

class ComplianceAnalyzer
{
    protected ThreatIntelligenceService $threatIntel;
    protected GeoLocationService $geoService;

    public function __construct()
    {
        $this->threatIntel = app(ThreatIntelligenceService::class);
        $this->geoService = app(GeoLocationService::class);
    }

    public function analyze(array $data): array
    {
        $attempts = $data['attempts'] ?? 0;
        $ip = $data['ip'] ?? request()->ip();
        $velocity = $this->calculateVelocity($ip);
        $timeOfDayRisk = $this->getTimeOfDayRisk();
        $baseScore = $this->score($attempts);
        $score = $baseScore;

        // Velocity bonus
        $velocityAdded = 0;
        $v = $velocity;
        if ($v > 10) {
            $velocityAdded = 25;
            $score += 25;
        } elseif ($v > 5) {
            $velocityAdded = 15;
            $score += 15;
        }

        // Threat intelligence
        $threatScore = $this->threatIntel->getIpRiskScore($ip);
        $score += $threatScore;

        // Geolocation risk
        $geoRisk = $this->geoService->getRiskScore($ip);
        $score += $geoRisk;

        // Time of day
        $score += $timeOfDayRisk;

        // Recidivism bonus
        $recidivismBonus = 0;
        $record = IpReputation::where('ip', $ip)->first();
        if ($record && $record->score > 60) {
            $recidivismBonus = 20;
            $score += 20;
        }

        // Cap and severity
        $score = min($score, 100);
        $severity = $this->severity($score);

        $analysisDetails = [
            'base_score' => $baseScore,
            'velocity' => $velocity,
            'velocity_bonus' => $velocityAdded,
            'threat_intel_score' => $threatScore,
            'geo_risk_score' => $geoRisk,
            'time_risk' => $timeOfDayRisk,
            'recidivism_bonus' => $recidivismBonus,
        ];

        $explanation = $this->generateExplanation($analysisDetails, $score, $attempts, $ip, $severity);

        return [
            'type' => $data['type'] ?? 'AUTH',
            'score' => $score,
            'severity' => $severity,
            'attempts' => $attempts,
            'analysis' => $analysisDetails,
            'explanation' => $explanation,
        ];
    }

    protected function generateExplanation(array $analysis, int $finalScore, int $attempts, string $ip, string $severity): string
    {
        $parts = [];

        // Base attempts
        $parts[] = "Base score for {$attempts} attempt(s): +{$analysis['base_score']}";

        // Velocity
        if ($analysis['velocity'] > 10) {
            $parts[] = "High velocity ({$analysis['velocity']} attempts/min): +{$analysis['velocity_bonus']}";
        } elseif ($analysis['velocity'] > 5) {
            $parts[] = "Elevated velocity ({$analysis['velocity']} attempts/min): +{$analysis['velocity_bonus']}";
        }

        // Threat intelligence
        if ($analysis['threat_intel_score'] > 0) {
            $parts[] = "IP known for abuse (AbuseIPDB): +{$analysis['threat_intel_score']}";
        }

        // Geolocation risk
        if ($analysis['geo_risk_score'] > 0) {
            $parts[] = "High‑risk country: +{$analysis['geo_risk_score']}";
        }

        // Time of day
        if ($analysis['time_risk'] > 0) {
            $hour = now()->hour;
            $parts[] = "Off‑hour activity ({$hour}:00): +{$analysis['time_risk']}";
        }

        // Recidivism
        if ($analysis['recidivism_bonus'] > 0) {
            $parts[] = "Repeat offender (past risk >60): +{$analysis['recidivism_bonus']}";
        }

        // Final
        $parts[] = "Total score {$finalScore} → {$severity} risk";

        return implode('; ', $parts);
    }

    private function calculateVelocity(string $ip): float
    {
        $key = "velocity_{$ip}";
        $timestamps = Cache::get($key, []);
        $now = now()->timestamp;

        $timestamps = array_filter($timestamps, fn($ts) => ($now - $ts) <= 60);
        $timestamps[] = $now;
        Cache::put($key, $timestamps, now()->addMinutes(1));

        return count($timestamps);
    }

    private function getTimeOfDayRisk(): int
    {
        $hour = now()->hour;
        if ($hour >= 2 && $hour <= 5) return 15;
        if ($hour >= 22 || $hour <= 6) return 10;
        return 0;
    }

    private function score(int $attempts): int
    {
        $score = $attempts * 10;
        if ($attempts > 5) $score += 20;
        if ($attempts > 10) $score += 30;
        return min($score, 100);
    }

    private function severity(int $score): string
    {
        return match (true) {
            $score >= 80 => 'HIGH',
            $score >= 50 => 'MEDIUM',
            default => 'LOW',
        };
    }
}
