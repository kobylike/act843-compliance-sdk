<?php

namespace GhanaCompliance\Act843SDK\Services\Security;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class GeoLocationService
{
    protected array $highRiskCountries = [
        'RU',
        'CN',
        'KP',
        'IR',
        'SY',
        'UA',
        'VN',
        'NG',
        'PK',
        'BD'
    ];

    protected array $mediumRiskCountries = [
        'IN',
        'BR',
        'MX',
        'ZA',
        'EG',
        'TR',
        'ID',
        'TH',
        'MY',
        'PH'
    ];

    public function getLocation(string $ip): array
    {
        $cacheKey = "geoip_{$ip}";
        if (Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        // Skip private IPs
        if ($this->isPrivateIp($ip)) {
            return ['country' => 'PRIVATE', 'risk_score' => 0];
        }

        try {
            // Using free ip-api.com (no API key required for non-commercial)
            $response = Http::timeout(2)
                ->get("http://ip-api.com/json/{$ip}?fields=status,country,countryCode,city,lat,lon,isp,org");

            if ($response->successful() && $response->json('status') === 'success') {
                $data = $response->json();
                $countryCode = $data['countryCode'] ?? 'UNKNOWN';
                $riskScore = $this->getCountryRiskScore($countryCode);

                $result = [
                    'country' => $data['country'] ?? 'Unknown',
                    'country_code' => $countryCode,
                    'city' => $data['city'] ?? 'Unknown',
                    'isp' => $data['isp'] ?? 'Unknown',
                    'org' => $data['org'] ?? 'Unknown',
                    'lat' => $data['lat'] ?? null,
                    'lon' => $data['lon'] ?? null,
                    'risk_score' => $riskScore,
                ];

                Cache::put($cacheKey, $result, now()->addDays(7));
                return $result;
            }
        } catch (\Exception $e) {
            Log::warning("GeoIP lookup failed for {$ip}: " . $e->getMessage());
        }

        return ['country' => 'UNKNOWN', 'risk_score' => 0];
    }

    public function getRiskScore(string $ip): int
    {
        $location = $this->getLocation($ip);
        return $location['risk_score'] ?? 0;
    }

    protected function getCountryRiskScore(string $countryCode): int
    {
        if (in_array($countryCode, $this->highRiskCountries)) return 20;
        if (in_array($countryCode, $this->mediumRiskCountries)) return 10;
        return 0;
    }

    protected function isPrivateIp(string $ip): bool
    {
        $privateRanges = [
            '10.0.0.0/8',
            '172.16.0.0/12',
            '192.168.0.0/16',
            '127.0.0.0/8',
            '::1',
            'fc00::/7'
        ];

        foreach ($privateRanges as $range) {
            if ($this->ipInRange($ip, $range)) return true;
        }
        return false;
    }

    protected function ipInRange(string $ip, string $range): bool
    {
        if (strpos($range, '/') === false) return $ip === $range;

        list($subnet, $mask) = explode('/', $range);
        $ipLong = ip2long($ip);
        $subnetLong = ip2long($subnet);
        $maskLong = -1 << (32 - (int)$mask);
        $subnetLong &= $maskLong;

        return ($ipLong & $maskLong) === $subnetLong;
    }
}
