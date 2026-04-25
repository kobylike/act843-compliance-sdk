<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use GhanaCompliance\Act843SDK\Models\ThreatIntelCache;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ThreatIntelligenceService
{
    // Free AbuseIPDB API (get your key from abuseipdb.com)
    protected string $apiKey;
    protected bool $enabled;

    public function __construct()
    {
        $this->apiKey = config('security.abuseipdb_api_key', '');
        $this->enabled = !empty($this->apiKey) && config('security.threat_intel_enabled', true);
    }

    public function getIpRiskScore(string $ip): int
    {
        if (!$this->enabled) return 0;

        // Check cache first (valid for 24 hours)
        $cacheKey = "threat_intel_{$ip}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $response = Http::timeout(3)
                ->withHeaders(['Key' => $this->apiKey, 'Accept' => 'application/json'])
                ->get('https://api.abuseipdb.com/api/v2/check', [
                    'ipAddress' => $ip,
                    'maxAgeInDays' => 90,
                ]);

            if ($response->successful()) {
                $data = $response->json();
                $confidenceScore = $data['data']['abuseConfidenceScore'] ?? 0;

                // Convert to our risk score (0-30)
                $riskScore = min(30, (int)($confidenceScore / 3.33));
                Cache::put($cacheKey, $riskScore, now()->addHours(24));

                // Store for analytics
                ThreatIntelCache::updateOrCreate(
                    ['ip' => $ip],
                    [
                        'abuse_score' => $confidenceScore,
                        'total_reports' => $data['data']['totalReports'] ?? 0,
                        'last_reported_at' => isset($data['data']['lastReportedAt'])
                            ? now()->parse($data['data']['lastReportedAt'])
                            : null,
                        'updated_at' => now(),
                    ]
                );

                return $riskScore;
            }
        } catch (\Exception $e) {
            Log::warning("Threat intel API failed for {$ip}: " . $e->getMessage());
        }

        return 0;
    }

    public function isKnownMalicious(string $ip): bool
    {
        return $this->getIpRiskScore($ip) >= 20;
    }
}
