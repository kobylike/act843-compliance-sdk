<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\IpReputation;
use GhanaCompliance\Act843SDK\Services\Security\GeoLocationService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class IpReputationEngine
{
    protected GeoLocationService $geoService;

    public function __construct()
    {
        $this->geoService = app(GeoLocationService::class);
    }

    public function update(string $ip, int $attempts, int $score): IpReputation
    {
        $record = IpReputation::firstOrCreate(
            ['ip' => $ip],
            [
                'score' => 0,
                'failures' => 0,
                'risk_level' => 'LOW',
                'total_failures' => 0,
            ]
        );

        $record->failures += 1;
        $record->total_failures += 1;

        // Geolocation enrichment
        $location = $this->geoService->getLocation($ip);
        $record->country = $location['country'] ?? null;
        $record->country_code = $location['country_code'] ?? null;
        $record->isp = $location['isp'] ?? null;

        // Time decay
        $timeDecay = $this->calculateTimeDecay($record);
        $record->score = min(
            ($record->failures * 5) + ($score * 0.6) + $timeDecay,
            100
        );

        $record->risk_level = match (true) {
            $record->score >= 80 => 'HIGH',
            $record->score >= 50 => 'MEDIUM',
            default => 'LOW',
        };

        $record->last_seen = now();
        $record->last_activity = now();
        // $record->blocked = false;

        $record->save();

        // ✅ Store ONLY primitive data, NOT the full model
        Cache::put("ip_reputation_{$ip}", [
            'score' => $record->score,
            'risk_level' => $record->risk_level,
            'failures' => $record->failures,
            // 'blocked' => false,
        ], now()->addMinutes(15));

        return $record;
    }

    protected function calculateTimeDecay(IpReputation $record): int
    {
        $hoursSinceLastSeen = $record->last_seen ? now()->diffInHours($record->last_seen) : 24;
        if ($hoursSinceLastSeen > 24) return -15;
        if ($hoursSinceLastSeen > 12) return -5;
        return 0;
    }

    public function decayAllScores(): int
    {
        return IpReputation::where('last_seen', '<', now()->subDays(7))
            ->update([
                'score' => DB::raw('GREATEST(0, score - (score * 0.1))'),
                'failures' => DB::raw('GREATEST(0, failures - 1)'),
            ]);
    }
}
