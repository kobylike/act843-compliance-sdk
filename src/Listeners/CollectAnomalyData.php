<?php

namespace GhanaCompliance\Act843SDK\Listeners;

use GhanaCompliance\Act843SDK\Models\AnomalyTrainingData;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;

class CollectAnomalyData
{
    public function handle($event)
    {
        $ip = request()->ip();
        $hour = now()->hour;
        $dayOfWeek = now()->dayOfWeek;
        $userAgentHash = hash_hmac('sha256', request()->userAgent(), config('app.key'));
        $ipClass = $this->classifyIp($ip);
        $requestRate = $this->getRequestRate($ip);

        AnomalyTrainingData::create([
            'hour' => $hour,
            'day_of_week' => $dayOfWeek,
            'user_agent_hash' => $userAgentHash,
            'ip_class' => $ipClass,
            'request_rate' => $requestRate,
        ]);
    }

    protected function classifyIp($ip): string
    {
        if (str_starts_with($ip, '127.') || str_starts_with($ip, '192.168.')) return 'private';
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)) return 'public';
        return 'unknown';
    }

    protected function getRequestRate($ip): float
    {
        $key = "req_rate_{$ip}";
        $timestamps = Cache::get($key, []);
        $now = now()->timestamp;
        $timestamps = array_filter($timestamps, fn($ts) => ($now - $ts) <= 60);
        $timestamps[] = $now;
        Cache::put($key, $timestamps, now()->addMinutes(1));
        return count($timestamps);
    }
}
